<?php

use App\Models\Announcement;
use App\Models\TenancyOccupant;

test('who may read an announcement of the property', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $announcement = Announcement::factory()->for($scenario['tenancy']->room->property)->create();

    expect($scenario[$actor]->can('view', $announcement))->toBe($allowed)
        ->and($scenario[$actor]->can('viewAny', Announcement::class))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'penghuni' => ['occupant', true],
    'teman sekamar' => ['roommate', true],
    'tetangga kamar lain di kost yang sama' => ['otherTenant', true],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
]);

test('tenants of another property cannot read its announcements', function () {
    $announcement = Announcement::factory()->create();
    $tenantElsewhere = TenancyOccupant::factory()->create()->user;

    expect($tenantElsewhere->can('view', $announcement))->toBeFalse();
});

test('announcements scheduled for later are hidden from tenants', function () {
    $scenario = accessScenario();
    $scheduled = Announcement::factory()->for($scenario['tenancy']->room->property)->create([
        'published_at' => now()->addDay(),
    ]);

    expect($scenario['occupant']->can('view', $scheduled))->toBeFalse()
        ->and($scenario['owner']->can('view', $scheduled))->toBeTrue();
});

test('only the owner manages announcements', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $announcement = Announcement::factory()->for($scenario['tenancy']->room->property)->create();
    $user = $scenario[$actor];

    expect($user->can('create', Announcement::class))->toBe($allowed)
        ->and($user->can('update', $announcement))->toBe($allowed)
        ->and($user->can('delete', $announcement))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'penghuni' => ['occupant', false],
]);
