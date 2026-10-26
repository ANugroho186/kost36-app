<?php

use App\Models\Tenancy;
use App\Models\TenancyOccupant;

test('who may view a tenancy', function (string $actor, bool $allowed) {
    $scenario = accessScenario();

    expect($scenario[$actor]->can('view', $scenario['tenancy']))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'penghuni sewa itu' => ['occupant', true],
    'teman sekamar' => ['roommate', true],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
    'penghuni kamar lain' => ['otherTenant', false],
]);

test('only the owner manages tenancies', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $user = $scenario[$actor];

    expect($user->can('viewAny', Tenancy::class))->toBe($allowed)
        ->and($user->can('create', Tenancy::class))->toBe($allowed)
        ->and($user->can('update', $scenario['tenancy']))->toBe($allowed)
        ->and($user->can('delete', $scenario['tenancy']))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'penghuni sewa itu' => ['occupant', false],
]);

test('occupants of an inactive tenancy keep access until they move out', function () {
    $tenancy = Tenancy::factory()->inactive()->create();
    $occupant = TenancyOccupant::factory()->for($tenancy)->create()->user;

    expect($occupant->can('view', $tenancy))->toBeTrue();
});

test('occupants lose access once the tenancy has ended', function () {
    $tenancy = Tenancy::factory()->ended()->create();
    // Sengaja belum diisi moved_out_at: status sewa ended saja sudah cukup untuk menutup akses.
    $occupant = TenancyOccupant::factory()->for($tenancy)->create()->user;

    expect($occupant->can('view', $tenancy))->toBeFalse();
});
