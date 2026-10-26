<?php

use App\Models\Complaint;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;

test('who may view a complaint about the room', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $complaint = Complaint::factory()->for($scenario['tenancy']->room)->for($scenario['occupant'], 'reporter')->create();

    expect($scenario[$actor]->can('view', $complaint))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'pelapor' => ['occupant', true],
    'teman sekamar' => ['roommate', true],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
    'tetangga kamar lain' => ['otherTenant', false],
]);

test('complaints from before the current tenancy are hidden from its occupants', function () {
    $scenario = accessScenario();
    $oldComplaint = Complaint::factory()->for($scenario['tenancy']->room)->create([
        'created_at' => $scenario['tenancy']->start_date->subDay(),
    ]);

    expect($scenario['occupant']->can('view', $oldComplaint))->toBeFalse()
        ->and($scenario['owner']->can('view', $oldComplaint))->toBeTrue();
});

test('who may report a complaint about a room', function (string $actor, bool $allowed) {
    $scenario = accessScenario();

    expect($scenario[$actor]->can('create', [Complaint::class, $scenario['tenancy']->room]))->toBe($allowed);
})->with([
    'pemilik kost (atas nama penghuni tanpa akun)' => ['owner', true],
    'penghuni kamar itu' => ['occupant', true],
    'teman sekamar' => ['roommate', true],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
    'tetangga kamar lain' => ['otherTenant', false],
]);

test('occupants of an inactive tenancy may still report complaints', function () {
    $tenancy = Tenancy::factory()->inactive()->create();
    $occupant = TenancyOccupant::factory()->for($tenancy)->create()->user;

    expect($occupant->can('create', [Complaint::class, $tenancy->room]))->toBeTrue();
});

test('only the owner handles complaints', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $complaint = Complaint::factory()->for($scenario['tenancy']->room)->for($scenario['occupant'], 'reporter')->create();
    $user = $scenario[$actor];

    expect($user->can('viewAny', Complaint::class))->toBe($allowed)
        ->and($user->can('update', $complaint))->toBe($allowed)
        ->and($user->can('delete', $complaint))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'pelapor' => ['occupant', false],
]);
