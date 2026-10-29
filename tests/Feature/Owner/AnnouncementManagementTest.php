<?php

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function announcementData(array $overrides = []): array
{
    return [
        'title' => 'Air mati hari Sabtu',
        'body' => 'Pompa air diperbaiki pukul 08.00 sampai 12.00.',
        'published_at' => '2026-11-01T18:00',
        'expires_at' => null,
        ...$overrides,
    ];
}

test('the status follows the show times, exactly at their edges', function (string $publishedAt, ?string $expiresAt, AnnouncementStatus $expected) {
    $this->travelTo('2026-11-01 18:00:00');

    $announcement = Announcement::factory()->make(['published_at' => $publishedAt, 'expires_at' => $expiresAt]);

    expect($announcement->status)->toBe($expected);
})->with([
    'mulai tayang semenit lagi' => ['2026-11-01 18:01:00', null, AnnouncementStatus::Scheduled],
    'mulai tayang tepat sekarang' => ['2026-11-01 18:00:00', null, AnnouncementStatus::Live],
    'tanpa batas tayang' => ['2026-10-01 08:00:00', null, AnnouncementStatus::Live],
    'batas tayang semenit lagi' => ['2026-10-01 08:00:00', '2026-11-01 18:01:00', AnnouncementStatus::Live],
    'batas tayang tepat sekarang' => ['2026-10-01 08:00:00', '2026-11-01 18:00:00', AnnouncementStatus::Expired],
]);

test('the owner sees every announcement with its status, latest first', function () {
    $this->travelTo('2026-11-01 12:00:00');
    $property = Property::factory()->create();
    $old = Announcement::factory()->for($property)->create(['published_at' => '2026-10-01 08:00', 'expires_at' => '2026-10-08 08:00']);
    $live = Announcement::factory()->for($property)->create(['published_at' => '2026-10-30 08:00']);
    $scheduled = Announcement::factory()->for($property)->create(['published_at' => '2026-11-05 18:00']);

    $response = $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.announcements.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('owner/announcements/index')
            ->where('canCreate', true)
            // Nilai awal form baru, dalam format input datetime-local.
            ->where('now', '2026-11-01T12:00'),
        );

    expect(array_column($response->inertiaProps('announcements'), 'id'))->toBe([$scheduled->id, $live->id, $old->id])
        ->and(array_column($response->inertiaProps('announcements'), 'status'))->toBe(['scheduled', 'live', 'expired']);
});

test('each announcement carries only what the owner page needs', function () {
    $announcement = Announcement::factory()->create([
        'published_at' => '2026-11-01 18:00',
        'expires_at' => '2026-11-03 06:30',
    ]);

    // Tanpa ->etc(): kunci tambahan membuat test gagal.
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.announcements.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('announcements.0', fn (Assert $item) => $item
                ->where('id', $announcement->id)
                ->where('title', $announcement->title)
                ->where('body', $announcement->body)
                ->where('publishedAt', '2026-11-01T18:00')
                ->where('expiresAt', '2026-11-03T06:30')
                ->has('status'),
            ),
        );
});

test('the owner writes an announcement and the time is kept in WIB', function () {
    $property = Property::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.announcements.store'), announcementData(['expires_at' => '2026-11-02T12:00']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.announcements.index'));

    $announcement = Announcement::query()->sole();

    expect($announcement->property_id)->toBe($property->id)
        ->and($announcement->title)->toBe('Air mati hari Sabtu')
        ->and($announcement->published_at->format('Y-m-d H:i e'))->toBe('2026-11-01 18:00 Asia/Jakarta')
        // Isi kolom di database: jam dinding WIB, bukan 11.00 (UTC).
        ->and(DB::table('announcements')->value('published_at'))->toBe('2026-11-01 18:00:00')
        ->and($announcement->expires_at?->format('Y-m-d H:i'))->toBe('2026-11-02 12:00');
});

test('announcements cannot be written before the property profile exists', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->get(route('owner.announcements.index'))
        ->assertInertia(fn (Assert $page) => $page->where('canCreate', false));
    $this->actingAs($owner)
        ->post(route('owner.announcements.store'), announcementData())
        ->assertRedirect(route('owner.property.edit'))
        ->assertInertiaFlash('toast.type', 'error');

    expect(Announcement::query()->count())->toBe(0);
});

test('announcements are validated', function (array $input, string $field) {
    Property::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.announcements.store'), announcementData($input))
        ->assertInvalid($field);

    expect(Announcement::query()->count())->toBe(0);
})->with([
    'tanpa judul' => [['title' => ''], 'title'],
    'tanpa isi' => [['body' => ''], 'body'],
    'tanpa waktu tayang' => [['published_at' => ''], 'published_at'],
    'waktu tayang bukan tanggal' => [['published_at' => 'besok sore'], 'published_at'],
    'batas tayang sebelum mulai tayang' => [['expires_at' => '2026-11-01T17:00'], 'expires_at'],
    'batas tayang sama dengan mulai tayang' => [['expires_at' => '2026-11-01T18:00'], 'expires_at'],
]);

test('the owner edits an announcement but cannot move it to another property', function () {
    $announcement = Announcement::factory()->create();
    $otherProperty = Property::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.announcements.update', $announcement), announcementData([
            'title' => 'Air mati hari Minggu',
            'property_id' => $otherProperty->id,
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.announcements.index'));

    $announcement->refresh();

    expect($announcement->title)->toBe('Air mati hari Minggu')
        ->and($announcement->property_id)->not->toBe($otherProperty->id);
});

test('the owner deletes an announcement', function () {
    $announcement = Announcement::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->delete(route('owner.announcements.destroy', $announcement))
        ->assertRedirect(route('owner.announcements.index'));

    expect(Announcement::find($announcement->id))->toBeNull();
});
