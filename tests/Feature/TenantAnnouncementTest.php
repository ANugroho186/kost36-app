<?php

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->travelTo('2026-11-01 18:00:00'));

test('the live scope matches the live status, exactly at the edges', function () {
    $times = [
        ['2026-11-01 18:01:00', null],                  // terjadwal semenit lagi
        ['2026-11-01 18:00:00', null],                  // mulai tayang tepat sekarang
        ['2026-10-01 08:00:00', '2026-11-01 18:01:00'], // batas tayang semenit lagi
        ['2026-10-01 08:00:00', '2026-11-01 18:00:00'], // batas tayang tepat sekarang
        ['2026-10-01 08:00:00', '2026-10-08 08:00:00'], // sudah lama kedaluwarsa
    ];
    foreach ($times as [$publishedAt, $expiresAt]) {
        Announcement::factory()->create(['published_at' => $publishedAt, 'expires_at' => $expiresAt]);
    }

    $liveByStatus = Announcement::all()->filter(fn (Announcement $a) => $a->status === AnnouncementStatus::Live)->pluck('id');

    expect(Announcement::query()->live()->orderBy('id')->pluck('id')->all())
        ->toBe($liveByStatus->values()->all())
        ->and($liveByStatus)->toHaveCount(2);
});

test('tenants read live announcements of their own property, newest first', function () {
    $scenario = accessScenario();
    $property = $scenario['tenancy']->room->property;
    $older = Announcement::factory()->for($property)->create(['published_at' => '2026-10-20 08:00']);
    $newer = Announcement::factory()->for($property)->create(['published_at' => '2026-10-30 08:00']);
    Announcement::factory()->for($property)->create(['published_at' => '2026-11-02 08:00']);
    Announcement::factory()->for($property)->expired()->create();
    Announcement::factory()->for(Property::factory())->create(['published_at' => '2026-10-31 08:00']);

    $listed = array_column(
        $this->actingAs($scenario['occupant'])->get(route('announcements.index'))->assertOk()->inertiaProps('announcements'),
        'id',
    );

    expect($listed)->toBe([$newer->id, $older->id]);

    // Daftar dan AnnouncementPolicy::view harus memakai aturan yang sama.
    Announcement::all()->each(fn (Announcement $announcement) => expect(in_array($announcement->id, $listed, true))
        ->toBe($scenario['occupant']->can('view', $announcement)));
});

test('the announcement list only sends what the page needs', function () {
    $scenario = accessScenario();
    $announcement = Announcement::factory()->for($scenario['tenancy']->room->property)->create([
        'published_at' => '2026-10-30 08:15',
        'expires_at' => '2026-11-30 08:00',
    ]);

    // Tanpa ->etc(): kunci tambahan membuat test gagal.
    $this->actingAs($scenario['roommate'])
        ->get(route('announcements.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('tenant/announcements/index')
            ->has('announcements', 1)
            ->has('announcements.0', fn (Assert $item) => $item
                ->where('id', $announcement->id)
                ->where('title', $announcement->title)
                ->where('body', $announcement->body)
                ->where('publishedAt', '2026-10-30T08:15'),
            ),
        );
});

test('tenants of an inactive tenancy still read announcements', function () {
    $tenancy = Tenancy::factory()->inactive()->create();
    $occupant = TenancyOccupant::factory()->for($tenancy)->create()->user;
    Announcement::factory()->for($tenancy->room->property)->create(['published_at' => '2026-10-30 08:00']);

    $this->actingAs($occupant)
        ->get(route('announcements.index'))
        ->assertInertia(fn (Assert $page) => $page->has('announcements', 1));
});

test('people who moved out see no announcements', function () {
    $scenario = accessScenario();
    Announcement::factory()->for($scenario['tenancy']->room->property)->create(['published_at' => '2026-10-30 08:00']);

    $this->actingAs($scenario['formerOccupant'])
        ->get(route('announcements.index'))
        ->assertInertia(fn (Assert $page) => $page->where('announcements', []));
});

test('the owner is sent to the owner announcement page', function () {
    $this->actingAs(accessScenario()['owner'])
        ->get(route('announcements.index'))
        ->assertRedirect(route('owner.announcements.index'));
});

test('guests are sent to the login page', function () {
    $this->get(route('announcements.index'))->assertRedirect(route('login'));
});

test('the tenant dashboard shows the three latest live announcements', function () {
    $scenario = accessScenario();
    $property = $scenario['tenancy']->room->property;
    $ids = collect(range(1, 4))
        ->map(fn (int $day) => Announcement::factory()->for($property)->create(['published_at' => "2026-10-2{$day} 08:00"])->id);
    Announcement::factory()->for($property)->create(['published_at' => '2026-11-02 08:00']);

    $shown = array_column(
        $this->actingAs($scenario['occupant'])->get(route('dashboard'))->inertiaProps('announcements'),
        'id',
    );

    // Terbaru di atas, dan yang terjadwal tidak ikut.
    expect($shown)->toBe($ids->reverse()->take(3)->values()->all());
});

test('a tenant dashboard without a room has no announcements', function () {
    $this->actingAs(accessScenario()['formerOccupant'])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('tenancy', null)->where('announcements', []));
});
