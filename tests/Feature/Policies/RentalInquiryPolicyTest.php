<?php

use App\Models\RentalInquiry;

test('only the owner reads and handles viewing requests', function (string $actor, bool $allowed) {
    $scenario = accessScenario();
    $inquiry = RentalInquiry::factory()->for($scenario['tenancy']->room->property)->create();
    $user = $scenario[$actor];

    expect($user->can('viewAny', RentalInquiry::class))->toBe($allowed)
        ->and($user->can('update', $inquiry))->toBe($allowed)
        ->and($user->can('delete', $inquiry))->toBe($allowed);
})->with([
    'pemilik kost' => ['owner', true],
    'penghuni' => ['occupant', false],
    'penghuni yang sudah keluar' => ['formerOccupant', false],
]);
