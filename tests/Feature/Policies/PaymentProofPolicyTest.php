<?php

use App\Models\PaymentProof;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;

test('who may see a payment proof status', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $proof = PaymentProof::factory()->for($scenario['tenancy'])->for($scenario['occupant'], 'uploader')->create();

    expect($scenario[$actor]->can('view', $proof))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'pengunggah' => ['occupant', true],
    'teman sekamar' => ['roommate', true],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
    'penghuni kamar lain' => ['otherTenant', false],
]);

test('who may open the payment proof image', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $proof = PaymentProof::factory()->for($scenario['tenancy'])->for($scenario['occupant'], 'uploader')->create();

    expect($scenario[$actor]->can('viewFile', $proof))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'pengunggah' => ['occupant', true],
    'teman sekamar' => ['roommate', false],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
    'penghuni kamar lain' => ['otherTenant', false],
]);

test('who may upload a payment proof for a tenancy', function (string $actor, bool $allowed) {
    $scenario = accessScenario();

    expect($scenario[$actor]->can('create', [PaymentProof::class, $scenario['tenancy']]))->toBe($allowed);
})->with([
    'pemilik kost (atas nama penghuni tanpa akun)' => ['owner', true],
    'penghuni sewa itu' => ['occupant', true],
    'teman sekamar' => ['roommate', true],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
    'penghuni kamar lain' => ['otherTenant', false],
]);

test('occupants of an inactive tenancy may still upload a payment proof', function () {
    $tenancy = Tenancy::factory()->inactive()->create();
    $occupant = TenancyOccupant::factory()->for($tenancy)->create()->user;

    expect($occupant->can('create', [PaymentProof::class, $tenancy]))->toBeTrue();
});

test('only the owner reviews, and only pending proofs', function () {
    $scenario = accessScenario();
    $pending = PaymentProof::factory()->for($scenario['tenancy'])->create();
    $approved = PaymentProof::factory()->for($scenario['tenancy'])->approved()->create();

    expect($scenario['owner']->can('review', $pending))->toBeTrue()
        ->and($scenario['owner']->can('review', $approved))->toBeFalse()
        ->and($scenario['occupant']->can('review', $pending))->toBeFalse()
        ->and($scenario['occupant']->can('viewAny', PaymentProof::class))->toBeFalse();
});
