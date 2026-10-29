<?php

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintPhoto;
use App\Models\MaintenanceLog;
use App\Models\Room;
use App\Models\User;
use App\Support\ImageUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Storage::fake('local'));

test('the owner filters complaints by whether they are still open', function (?string $filter, array $expected) {
    Complaint::factory()->create(['title' => 'Lampu mati', 'created_at' => now()->subDays(3)]);
    Complaint::factory()->inProgress()->create(['title' => 'Keran bocor', 'created_at' => now()->subDays(2)]);
    Complaint::factory()->resolved()->create(['title' => 'Pintu macet', 'created_at' => now()->subDay()]);

    $response = $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.complaints.index', ['status' => $filter]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('owner/complaints/index')
            ->where('filter', in_array($filter, ['resolved', 'all'], true) ? $filter : 'open')
            ->where('counts', ['open' => 2, 'resolved' => 1]),
        );

    expect(array_column($response->inertiaProps('complaints'), 'title'))->toBe($expected);
})->with([
    'bawaan: yang masih terbuka' => [null, ['Keran bocor', 'Lampu mati']],
    'selesai' => ['resolved', ['Pintu macet']],
    'semua, terbaru di atas' => ['all', ['Pintu macet', 'Keran bocor', 'Lampu mati']],
    'nilai tidak dikenal dianggap terbuka' => ['aneh', ['Keran bocor', 'Lampu mati']],
]);

test('each complaint carries only what the owner page needs', function () {
    $reporter = User::factory()->create(['name' => 'Budi']);
    $complaint = Complaint::factory()->for($reporter, 'reporter')->resolved()->create();
    $photo = ComplaintPhoto::factory()->for($complaint)->create();
    $repair = MaintenanceLog::factory()->forRoom($complaint->room)->for($complaint)->create(['performed_at' => '2026-10-20']);

    // Tanpa ->etc(): kunci tambahan (misalnya path file atau kontak pelapor) membuat test gagal.
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.complaints.index', ['status' => 'all']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('complaints', 1)
            ->has('complaints.0', fn (Assert $item) => $item
                ->where('id', $complaint->id)
                ->where('roomNumber', $complaint->room->number)
                ->where('title', $complaint->title)
                ->where('description', $complaint->description)
                ->where('status', 'resolved')
                ->where('reportedAt', $complaint->created_at->toDateString())
                ->where('resolvedAt', $complaint->resolved_at->toDateString())
                ->where('reporter', 'Budi')
                ->where('photos', [['id' => $photo->id, 'url' => route('complaints.photos.show', [$complaint, $photo])]])
                ->where('repairs', [['id' => $repair->id, 'performedAt' => '2026-10-20', 'description' => $repair->description]]),
            ),
        );
});

test('the owner records a complaint for a tenant without a login', function () {
    $room = Room::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.complaints.store'), [
            'room_id' => $room->id,
            'title' => 'Kipas rusak',
            'description' => 'Diceritakan langsung oleh penghuni kamar ini.',
            'photos' => [UploadedFile::fake()->image('kipas.jpg')],
            // Diabaikan: keluhan baru selalu berstatus dilaporkan.
            'status' => ComplaintStatus::Resolved->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.complaints.index'));

    $complaint = Complaint::query()->sole();

    expect($complaint->room_id)->toBe($room->id)
        ->and($complaint->reporter_id)->toBeNull()
        ->and($complaint->status)->toBe(ComplaintStatus::Reported);
    Storage::disk('local')->assertExists($complaint->photos()->sole()->path);
});

test('a recorded complaint needs an existing room', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.complaints.store'), ['room_id' => 999, 'title' => 'Kipas rusak', 'description' => 'Rusak.'])
        ->assertInvalid('room_id');

    expect(Complaint::query()->count())->toBe(0);
});

test('starting work moves a complaint to in progress', function () {
    $complaint = Complaint::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.complaints.index'))
        ->put(route('owner.complaints.update', $complaint), ['status' => 'in_progress'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.complaints.index'));

    expect($complaint->fresh()->status)->toBe(ComplaintStatus::InProgress)
        ->and($complaint->fresh()->resolved_at)->toBeNull();
});

test('resolving with a repair note writes it to the maintenance log', function () {
    // Kolom timestamp hanya menyimpan sampai detik, jadi waktu dibekukan di awal detik.
    $this->freezeSecond();
    $complaint = Complaint::factory()->inProgress()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.complaints.update', $complaint), [
            'status' => 'resolved',
            'repair_description' => 'Karet keran diganti.',
            'repaired_on' => today()->subDay()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    $complaint->refresh();
    $log = MaintenanceLog::query()->sole();

    expect($complaint->status)->toBe(ComplaintStatus::Resolved)
        ->and($complaint->resolved_at?->equalTo(now()))->toBeTrue()
        ->and($log->complaint_id)->toBe($complaint->id)
        ->and($log->room_id)->toBe($complaint->room_id)
        // Kost dari kamar keluhan, bukan dari input.
        ->and($log->property_id)->toBe($complaint->room->property_id)
        ->and($log->performed_at->toDateString())->toBe(today()->subDay()->toDateString())
        ->and($log->description)->toBe('Karet keran diganti.');
});

test('resolving without a repair note leaves the maintenance log alone', function () {
    $complaint = Complaint::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.complaints.update', $complaint), ['status' => 'resolved', 'repaired_on' => today()->toDateString()])
        ->assertSessionHasNoErrors();

    expect($complaint->fresh()->status)->toBe(ComplaintStatus::Resolved)
        ->and(MaintenanceLog::query()->count())->toBe(0);
});

test('a repair note is ignored unless the complaint is being resolved', function () {
    $complaint = Complaint::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.complaints.update', $complaint), [
            'status' => 'in_progress',
            'repair_description' => 'Belum selesai.',
            'repaired_on' => today()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect(MaintenanceLog::query()->count())->toBe(0);
});

test('reopening clears the resolved date but keeps the repair notes', function () {
    $complaint = Complaint::factory()->resolved()->create();
    MaintenanceLog::factory()->forRoom($complaint->room)->for($complaint)->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.complaints.update', $complaint), ['status' => 'in_progress'])
        ->assertSessionHasNoErrors();

    expect($complaint->fresh()->status)->toBe(ComplaintStatus::InProgress)
        ->and($complaint->fresh()->resolved_at)->toBeNull()
        ->and($complaint->maintenanceLogs()->count())->toBe(1);
});

test('status changes are validated', function (array $input, string $field) {
    $complaint = Complaint::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.complaints.update', $complaint), $input)
        ->assertInvalid($field);

    expect($complaint->fresh()->status)->toBe(ComplaintStatus::Reported)
        ->and(MaintenanceLog::query()->count())->toBe(0);
})->with([
    'status tidak dikenal' => [['status' => 'done'], 'status'],
    'catatan tanpa tanggal' => [['status' => 'resolved', 'repair_description' => 'Diganti.'], 'repaired_on'],
    'tanggal perbaikan di masa depan' => [
        ['status' => 'resolved', 'repair_description' => 'Diganti.', 'repaired_on' => '2999-01-01'],
        'repaired_on',
    ],
]);

test('deleting a complaint removes its photo files but keeps the repair notes', function () {
    $complaint = Complaint::factory()->create();
    $path = app(ImageUpload::class)->store(UploadedFile::fake()->image('foto.jpg'), 'complaints', 'local');
    ComplaintPhoto::factory()->for($complaint)->create(['path' => $path]);
    $repair = MaintenanceLog::factory()->forRoom($complaint->room)->for($complaint)->create();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.complaints.index'))
        ->delete(route('owner.complaints.destroy', $complaint))
        ->assertRedirect(route('owner.complaints.index'));

    expect(Complaint::find($complaint->id))->toBeNull()
        ->and(ComplaintPhoto::query()->count())->toBe(0)
        ->and($repair->fresh()->complaint_id)->toBeNull();
    Storage::disk('local')->assertMissing($path);
});
