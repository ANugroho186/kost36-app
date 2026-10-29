<?php

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Storage::fake('local'));

test('occupants see their room complaints from the current tenancy only', function () {
    $scenario = accessScenario();
    $room = $scenario['tenancy']->room;

    $onFirstDay = Complaint::factory()->for($room)->create(['created_at' => $scenario['tenancy']->start_date]);
    $mine = Complaint::factory()->for($room)->for($scenario['occupant'], 'reporter')->create(['created_at' => now()->subDays(2)]);
    $roommates = Complaint::factory()->for($room)->for($scenario['roommate'], 'reporter')->resolved()->create(['created_at' => now()->subDay()]);
    // Dari penghuni lama kamar yang sama, sebelum sewa ini dimulai.
    Complaint::factory()->for($room)->create(['created_at' => $scenario['tenancy']->start_date->subSecond()]);
    Complaint::factory()->for($scenario['otherTenant']->currentTenancy()->room)->create();

    $listed = array_column(
        $this->actingAs($scenario['occupant'])->get(route('complaints.index'))->assertOk()->inertiaProps('complaints'),
        'id',
    );

    // Terbaru di atas.
    expect($listed)->toBe([$roommates->id, $mine->id, $onFirstDay->id]);

    // Query daftar dan ComplaintPolicy::view harus memakai aturan yang sama.
    Complaint::all()->each(fn (Complaint $complaint) => expect(in_array($complaint->id, $listed, true))
        ->toBe($scenario['occupant']->can('view', $complaint)));
});

test('the complaint list only sends what the page needs', function () {
    $scenario = accessScenario();
    $complaint = Complaint::factory()->for($scenario['tenancy']->room)->for($scenario['roommate'], 'reporter')->create();
    $photo = ComplaintPhoto::factory()->for($complaint)->create();

    // Tanpa ->etc(): kunci tambahan (misalnya pelapor atau path file) membuat test gagal.
    $this->actingAs($scenario['occupant'])
        ->get(route('complaints.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('tenant/complaints/index')
            ->where('roomNumber', $scenario['tenancy']->room->number)
            ->has('complaints', 1)
            ->has('complaints.0', fn (Assert $item) => $item
                ->where('id', $complaint->id)
                ->where('title', $complaint->title)
                ->where('description', $complaint->description)
                ->where('status', 'reported')
                ->where('reportedAt', $complaint->created_at->toDateString())
                ->where('resolvedAt', null)
                ->where('photos', [['id' => $photo->id, 'url' => route('complaints.photos.show', [$complaint, $photo])]]),
            ),
        );
});

test('people without a room see an empty complaint page', function () {
    $scenario = accessScenario();

    $this->actingAs($scenario['formerOccupant'])
        ->get(route('complaints.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('roomNumber', null)
            ->where('complaints', [])
            ->where('canReport', false),
        );
});

test('occupants are offered the report form for their own room', function () {
    $scenario = accessScenario();

    $this->actingAs($scenario['roommate'])
        ->get(route('complaints.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('roomNumber', $scenario['tenancy']->room->number)
            ->where('canReport', true)
            ->where('maxPhotos', 4),
        );
});

test('an occupant reports a problem with photos kept on the private disk', function () {
    Storage::fake('public');
    $scenario = accessScenario();

    $this->actingAs($scenario['occupant'])
        ->post(route('complaints.store'), [
            'title' => 'Keran bocor',
            'description' => 'Keran kamar mandi menetes terus sejak kemarin.',
            'photos' => [
                UploadedFile::fake()->image('keran.jpg', 2400, 1800),
                UploadedFile::fake()->image('keran-dekat.png', 400, 300),
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('complaints.index'));

    $complaint = Complaint::query()->sole();

    expect($complaint->room_id)->toBe($scenario['tenancy']->room_id)
        ->and($complaint->reporter_id)->toBe($scenario['occupant']->id)
        ->and($complaint->status)->toBe(ComplaintStatus::Reported)
        ->and($complaint->photos)->toHaveCount(2);

    foreach ($complaint->photos as $photo) {
        expect($photo->path)->toStartWith('complaints/')->toEndWith('.webp')->not->toContain('keran');
        Storage::disk('local')->assertExists($photo->path);
    }

    // Disk public bisa dibuka siapa saja tanpa login, jadi tidak boleh ada foto keluhan di sana.
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('photos are optional', function () {
    $scenario = accessScenario();

    $this->actingAs($scenario['roommate'])
        ->post(route('complaints.store'), ['title' => 'Lampu mati', 'description' => 'Lampu kamar tidak menyala.'])
        ->assertSessionHasNoErrors();

    expect(Complaint::query()->sole()->photos)->toHaveCount(0);
});

test('the room, reporter and status cannot be chosen by the occupant', function () {
    $scenario = accessScenario();

    $this->actingAs($scenario['occupant'])
        ->post(route('complaints.store'), [
            'title' => 'Lampu mati',
            'description' => 'Lampu kamar tidak menyala.',
            'room_id' => $scenario['otherTenant']->currentTenancy()->room_id,
            'reporter_id' => $scenario['roommate']->id,
            'status' => ComplaintStatus::Resolved->value,
            'resolved_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasNoErrors();

    $complaint = Complaint::query()->sole();

    expect($complaint->room_id)->toBe($scenario['tenancy']->room_id)
        ->and($complaint->reporter_id)->toBe($scenario['occupant']->id)
        ->and($complaint->status)->toBe(ComplaintStatus::Reported)
        ->and($complaint->resolved_at)->toBeNull();
});

test('complaints are validated', function (array $input, array $errors) {
    $scenario = accessScenario();

    $this->actingAs($scenario['occupant'])
        ->post(route('complaints.store'), [
            'title' => 'Lampu mati',
            'description' => 'Lampu kamar tidak menyala.',
            ...$input,
        ])
        ->assertInvalid($errors);

    expect(Complaint::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'tanpa judul' => [fn () => ['title' => ''], ['title']],
    'tanpa keterangan' => [fn () => ['description' => ''], ['description']],
    'lebih dari 4 foto' => [
        fn () => ['photos' => array_map(fn (int $i) => UploadedFile::fake()->image("foto-{$i}.jpg"), range(1, 5))],
        ['photos' => 'at most 4 photos'],
    ],
    'bukan gambar' => [
        fn () => ['photos' => [UploadedFile::fake()->create('nota.pdf', 100, 'application/pdf')]],
        ['photos.0' => 'Only JPG, PNG or WebP'],
    ],
    'foto mode 50 MP' => [
        fn () => ['photos' => [UploadedFile::fake()->image('besar.jpg', 6001, 10)]],
        ['photos.0' => 'normal mode'],
    ],
]);

test('only people living in a room can report from this page', function (string $actor) {
    $scenario = accessScenario();

    $this->actingAs($scenario[$actor])
        ->get(route('complaints.index'))
        ->assertInertia(fn (Assert $page) => $page->where('canReport', false));
    // Data kosong tetap 403, bukan error validasi: hak akses dicek lebih dulu.
    $this->actingAs($scenario[$actor])->post(route('complaints.store'), [])->assertForbidden();

    expect(Complaint::query()->count())->toBe(0);
})->with([
    'penghuni yang sudah keluar' => ['formerOccupant'],
    // Pemilik mencatat keluhan atas nama penghuni dari halamannya sendiri (langkah 1b).
    'pemilik kost' => ['owner'],
]);

test('guests are sent to the login page', function (string $method, string $route) {
    $this->call($method, route($route))->assertRedirect(route('login'));
})->with([
    ['GET', 'complaints.index'],
    ['POST', 'complaints.store'],
]);

test('photos are removed again when the complaint cannot be saved', function () {
    $scenario = accessScenario();
    // Meniru kegagalan database setelah foto selesai diolah dan disimpan.
    Complaint::creating(fn () => throw new RuntimeException('Database tidak bisa dihubungi.'));

    $this->actingAs($scenario['occupant'])
        ->post(route('complaints.store'), [
            'title' => 'Keran bocor',
            'description' => 'Keran kamar mandi menetes terus.',
            'photos' => [UploadedFile::fake()->image('keran.jpg'), UploadedFile::fake()->image('keran-2.jpg')],
        ])
        ->assertServerError();

    expect(Storage::disk('local')->allFiles())->toBe([]);
});
