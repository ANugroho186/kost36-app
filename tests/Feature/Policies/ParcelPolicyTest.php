<?php

use App\Models\Parcel;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;

test('who may view a parcel', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create();

    expect($scenario[$actor]->can('view', $parcel))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'pemilik paket' => ['occupant', true],
    'teman sekamar' => ['roommate', false],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
    'tetangga kamar lain' => ['otherTenant', false],
]);

test('only the parcel owner may see the item name, not even the property owner', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create();

    expect($scenario[$actor]->can('viewItemName', $parcel))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', false],
    'pemilik paket' => ['occupant', true],
    'teman sekamar' => ['roommate', false],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
    'tetangga kamar lain' => ['otherTenant', false],
]);

test('who may register and list parcels', function (string $actor, bool $canRegister, bool $canList) {
    $scenario = accessScenario();

    expect($scenario[$actor]->can('create', Parcel::class))->toBe($canRegister)
        ->and($scenario[$actor]->can('viewAny', Parcel::class))->toBe($canList);
})->with([
    'pemilik kost' => ['owner', false, true],
    'penghuni' => ['occupant', true, true],
    'tetangga kamar lain' => ['otherTenant', true, true],
    'penghuni yang sudah keluar' => ['formerOccupant', false, false],
]);

test('occupants of an inactive tenancy may still register parcels', function () {
    $tenancy = Tenancy::factory()->inactive()->create();
    $occupant = TenancyOccupant::factory()->for($tenancy)->create()->user;

    expect($occupant->can('create', Parcel::class))->toBeTrue();
});

test('the parcel owner may edit or cancel only while it is waiting', function (array $times, bool $allowed) {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create($times);

    expect($scenario['occupant']->can('update', $parcel))->toBe($allowed)
        ->and($scenario['occupant']->can('delete', $parcel))->toBe($allowed)
        ->and($scenario['roommate']->can('delete', $parcel))->toBeFalse()
        ->and($scenario['owner']->can('delete', $parcel))->toBeFalse();
})->with([
    'menunggu' => [[], true],
    'di rumah pemilik' => [['held_by_owner_at' => '2026-10-20 10:00:00'], false],
    'diterima langsung' => [['collected_at' => '2026-10-21 18:00:00'], false],
]);

test('only the property owner marks a waiting parcel as held', function (array $times, bool $allowed) {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create($times);

    expect($scenario['owner']->can('markHeld', $parcel))->toBe($allowed)
        ->and($scenario['occupant']->can('markHeld', $parcel))->toBeFalse();
})->with([
    'menunggu' => [[], true],
    'sudah di rumah pemilik' => [['held_by_owner_at' => '2026-10-20 10:00:00'], false],
    'diterima langsung' => [['collected_at' => '2026-10-21 18:00:00'], false],
]);

test('only the parcel owner marks it as collected, until it has been collected', function (array $times, bool $allowed) {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create($times);

    expect($scenario['occupant']->can('markCollected', $parcel))->toBe($allowed)
        ->and($scenario['roommate']->can('markCollected', $parcel))->toBeFalse()
        ->and($scenario['owner']->can('markCollected', $parcel))->toBeFalse();
})->with([
    'menunggu (diterima langsung dari kurir)' => [[], true],
    'di rumah pemilik' => [['held_by_owner_at' => '2026-10-20 10:00:00'], true],
    'sudah diambil' => [['held_by_owner_at' => '2026-10-20 10:00:00', 'collected_at' => '2026-10-21 18:00:00'], false],
    'sudah diterima langsung' => [['collected_at' => '2026-10-21 18:00:00'], false],
]);
