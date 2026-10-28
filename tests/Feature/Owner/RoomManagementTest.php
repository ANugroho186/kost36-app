<?php

use App\Models\Complaint;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomPhoto;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function roomData(array $overrides = []): array
{
    return [
        'number' => '5',
        'capacity' => 2,
        'facilities' => 'Kasur, lemari',
        'is_published' => '1',
        ...$overrides,
    ];
}

test('the owner sees every room with its occupancy', function () {
    $this->seed(DemoSeeder::class);

    $this->actingAs(User::firstWhere('username', 'pemilik'))
        ->get(route('owner.rooms.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('owner/rooms/index')
            ->has('rooms', 4)
            ->where('rooms.0.number', '1')
            ->where('rooms.0.occupants', 2)
            ->where('rooms.0.isOccupied', true)
            ->where('rooms.3.number', '4')
            ->where('rooms.3.occupants', 0)
            ->where('rooms.3.isOccupied', false),
        );
});

test('the owner adds a room', function () {
    $owner = User::factory()->owner()->create();
    $property = Property::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('owner.rooms.store'), roomData())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.rooms.index'));

    $room = Room::sole();

    expect($room->property_id)->toBe($property->id)
        ->and($room->number)->toBe('5')
        ->and($room->is_published)->toBeTrue();
});

test('an unticked publish box hides the room', function () {
    $owner = User::factory()->owner()->create();
    Property::factory()->for($owner, 'owner')->create();

    // Checkbox yang tidak dicentang tidak ikut terkirim sama sekali.
    $data = roomData();
    unset($data['is_published']);

    $this->actingAs($owner)->post(route('owner.rooms.store'), $data)->assertSessionHasNoErrors();

    expect(Room::sole()->is_published)->toBeFalse();
});

test('rooms cannot be added before the property profile exists', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->get(route('owner.rooms.create'))->assertRedirect(route('owner.property.edit'));
    $this->actingAs($owner)->post(route('owner.rooms.store'), roomData())->assertRedirect(route('owner.property.edit'));

    expect(Room::count())->toBe(0);
});

test('room numbers must be unique within the property', function () {
    $owner = User::factory()->owner()->create();
    $property = Property::factory()->for($owner, 'owner')->create();
    Room::factory()->for($property)->create(['number' => '5']);

    $this->actingAs($owner)
        ->from(route('owner.rooms.create'))
        ->post(route('owner.rooms.store'), roomData(['number' => '5']))
        ->assertSessionHasErrors('number');
});

test('capacity must be between one and two people', function (int $capacity) {
    $owner = User::factory()->owner()->create();
    Property::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->from(route('owner.rooms.create'))
        ->post(route('owner.rooms.store'), roomData(['capacity' => $capacity]))
        ->assertSessionHasErrors('capacity');
})->with([0, 3]);

test('capacity cannot drop below the current occupants', function () {
    $owner = User::factory()->owner()->create();
    $tenancy = Tenancy::factory()->create();
    TenancyOccupant::factory()->for($tenancy)->count(2)->create();

    $this->actingAs($owner)
        ->from(route('owner.rooms.edit', $tenancy->room))
        ->put(route('owner.rooms.update', $tenancy->room), roomData([
            'number' => $tenancy->room->number,
            'capacity' => 1,
        ]))
        ->assertSessionHasErrors('capacity');

    expect($tenancy->room->fresh()->capacity)->toBe(2);
});

test('the owner updates a room and keeps its own number', function () {
    $owner = User::factory()->owner()->create();
    $room = Room::factory()->published()->create(['number' => '7']);

    $data = roomData(['number' => '7', 'facilities' => 'Kasur, AC']);
    unset($data['is_published']);

    $this->actingAs($owner)
        ->put(route('owner.rooms.update', $room), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.rooms.index'));

    $room->refresh();

    expect($room->facilities)->toBe('Kasur, AC')
        ->and($room->is_published)->toBeFalse();
});

test('the owner deletes a room without history, including its photo files', function () {
    Storage::fake('public');
    Storage::disk('public')->put('rooms/contoh.jpg', 'gambar');

    $owner = User::factory()->owner()->create();
    $room = Room::factory()->create();
    RoomPhoto::factory()->for($room)->create(['path' => 'rooms/contoh.jpg']);

    $this->actingAs($owner)
        ->delete(route('owner.rooms.destroy', $room))
        ->assertRedirect(route('owner.rooms.index'));

    expect(Room::find($room->id))->toBeNull();
    Storage::disk('public')->assertMissing('rooms/contoh.jpg');
});

test('rooms with history are kept, with a clear message instead of an error', function (string $history) {
    $owner = User::factory()->owner()->create();
    $room = Room::factory()->create();

    match ($history) {
        'sewa' => Tenancy::factory()->for($room)->ended()->create(),
        'keluhan' => Complaint::factory()->for($room)->create(),
    };

    $this->actingAs($owner)
        ->from(route('owner.rooms.edit', $room))
        ->delete(route('owner.rooms.destroy', $room))
        ->assertRedirect(route('owner.rooms.edit', $room));

    expect(Room::find($room->id))->not->toBeNull();
})->with(['sewa', 'keluhan']);
