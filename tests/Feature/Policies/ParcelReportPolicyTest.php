<?php

use App\Models\ParcelReport;

test('who may report a wrongly received parcel', function (string $actor, bool $allowed) {
    $scenario = accessScenario();

    expect($scenario[$actor]->can('create', ParcelReport::class))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', false],
    'penghuni' => ['occupant', true],
    'tetangga kamar lain' => ['otherTenant', true],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
]);

test('who may view a report', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $report = ParcelReport::factory()->for($scenario['occupant'], 'reporter')->create();

    expect($scenario[$actor]->can('view', $report))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'pelapor' => ['occupant', true],
    'teman sekamar' => ['roommate', false],
    'tetangga kamar lain' => ['otherTenant', false],
]);

test('only the property owner links and resolves open reports', function () {
    $scenario = accessScenario();
    $open = ParcelReport::factory()->for($scenario['occupant'], 'reporter')->create();
    $resolved = ParcelReport::factory()->for($scenario['occupant'], 'reporter')->resolved()->create();

    expect($scenario['owner']->can('link', $open))->toBeTrue()
        ->and($scenario['owner']->can('resolve', $open))->toBeTrue()
        ->and($scenario['owner']->can('link', $resolved))->toBeFalse()
        ->and($scenario['owner']->can('resolve', $resolved))->toBeFalse()
        ->and($scenario['occupant']->can('link', $open))->toBeFalse()
        ->and($scenario['occupant']->can('viewAny', ParcelReport::class))->toBeFalse();
});
