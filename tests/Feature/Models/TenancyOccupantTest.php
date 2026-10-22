<?php

use App\Enums\NotifyChannel;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('an account can be an active occupant in only one room', function () {
    $user = User::factory()->create();
    TenancyOccupant::factory()->for($user)->create();

    TenancyOccupant::factory()->for($user)->create();
})->throws(UniqueConstraintViolationException::class);

test('an account that moved out can be active in another room', function () {
    $user = User::factory()->create();
    TenancyOccupant::factory()->for($user)->movedOut()->create();

    TenancyOccupant::factory()->for($user)->create();

    expect($user->occupancies()->count())->toBe(2);
});

test('occupants without an account are not limited', function () {
    $tenancy = Tenancy::factory()->create();

    TenancyOccupant::factory()->for($tenancy)->withoutAccount()->count(2)->create();

    expect($tenancy->occupants()->count())->toBe(2);
});

test('active occupants exclude those who moved out', function () {
    $tenancy = Tenancy::factory()->create();
    $staying = TenancyOccupant::factory()->for($tenancy)->create();
    TenancyOccupant::factory()->for($tenancy)->movedOut()->create();

    expect($tenancy->activeOccupants->modelKeys())->toBe([$staying->id]);
});

test('user account cannot be linked through mass assignment', function () {
    $occupant = new TenancyOccupant(['user_id' => 1]);

    expect($occupant->user_id)->toBeNull();
});

test('deleting an account keeps the occupant record without the link', function () {
    $occupant = TenancyOccupant::factory()->create();

    $occupant->user->delete();

    expect($occupant->fresh()->user_id)->toBeNull();
});

test('deleting a tenancy deletes its occupants', function () {
    $tenancy = Tenancy::factory()->create();
    TenancyOccupant::factory()->for($tenancy)->count(2)->create();

    $tenancy->delete();

    expect(TenancyOccupant::count())->toBe(0);
});

test('notification contact is hidden when serialized', function () {
    $occupant = TenancyOccupant::factory()->create([
        'notify_channel' => NotifyChannel::Telegram,
        'notify_contact' => '123456789',
    ]);

    expect($occupant->toArray())->not->toHaveKey('notify_contact')
        ->and($occupant->notify_channel)->toBe(NotifyChannel::Telegram);
});
