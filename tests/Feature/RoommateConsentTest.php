<?php

use App\Models\Room;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('the occupant or the owner may open the room for someone they know', function (string $actor, bool $allowed) {
    // Kamar berdua yang baru berisi 1 orang, dan seorang tetangga di kamar lain pada kost yang sama.
    $tenancy = Tenancy::factory()->create();
    $users = [
        'occupant' => TenancyOccupant::factory()->for($tenancy)->create()->user,
        'owner' => User::factory()->owner()->create(),
        'neighbour' => TenancyOccupant::factory()
            ->for(Tenancy::factory()->for(Room::factory()->for($tenancy->room->property)))
            ->create()->user,
    ];

    $response = $this->actingAs($users[$actor])
        ->from(route('dashboard'))
        ->put(route('tenancies.accepts-roommate', $tenancy), ['accepts_roommate' => '1']);

    $allowed ? $response->assertRedirect(route('dashboard')) : $response->assertForbidden();
    expect($tenancy->fresh()->accepts_roommate)->toBe($allowed);
})->with([
    'penghuni kamar itu' => ['occupant', true],
    'pemilik kost' => ['owner', true],
    'tetangga kamar lain' => ['neighbour', false],
]);

test('the room can be marked as full again', function () {
    $tenancy = Tenancy::factory()->create(['accepts_roommate' => true]);
    $occupant = TenancyOccupant::factory()->for($tenancy)->create()->user;

    $this->actingAs($occupant)
        ->from(route('dashboard'))
        ->put(route('tenancies.accepts-roommate', $tenancy), ['accepts_roommate' => '0'])
        ->assertSessionHasNoErrors();

    expect($tenancy->fresh()->accepts_roommate)->toBeFalse();
});

test('a full room cannot be opened for another person', function () {
    $tenancy = Tenancy::factory()->create();
    $occupant = TenancyOccupant::factory()->for($tenancy)->create()->user;
    TenancyOccupant::factory()->for($tenancy)->create();

    $this->actingAs($occupant)
        ->from(route('dashboard'))
        ->put(route('tenancies.accepts-roommate', $tenancy), ['accepts_roommate' => '1'])
        ->assertSessionHasErrors('accepts_roommate');

    expect($tenancy->fresh()->accepts_roommate)->toBeFalse();
});

test('a room waiting to be vacated cannot be opened for sharing', function () {
    $tenancy = Tenancy::factory()->inactive()->create();
    $occupant = TenancyOccupant::factory()->for($tenancy)->create()->user;

    $this->actingAs($occupant)
        ->put(route('tenancies.accepts-roommate', $tenancy), ['accepts_roommate' => '1'])
        ->assertForbidden();
});

test('the tenant dashboard offers the sharing switch only when there is space', function (string $username, bool $offered) {
    $this->seed(DemoSeeder::class);

    $this->actingAs(User::firstWhere('username', $username))
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tenancy.canToggleRoommate', $offered)
            ->where('tenancy.acceptsRoommate', false),
        );
})->with([
    'Citra, sendirian di kamar berdua' => ['penghuni3', true],
    'Budi, kamarnya sudah berisi 2' => ['penghuni1', false],
    'Dodi, sewanya nonaktif' => ['penghuni4', false],
]);
