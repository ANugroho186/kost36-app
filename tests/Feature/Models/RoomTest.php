<?php

use App\Models\Property;
use App\Models\Room;
use App\Models\RoomPhoto;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('owner creates properties and rooms through relations', function () {
    $owner = User::factory()->owner()->create();

    $property = $owner->properties()->create([
        'name' => 'Kost Contoh',
        'address' => 'Jl. Contoh No. 1',
        'description' => 'Kost dummy',
        'rules' => 'Aturan dummy',
    ]);
    $room = $property->rooms()->create(['number' => '1', 'facilities' => 'Kasur']);

    expect($property->owner->is($owner))->toBeTrue()
        ->and($room->property->is($property))->toBeTrue();
});

test('property owner cannot be mass assigned', function () {
    $property = new Property(['owner_id' => 1]);

    expect($property->owner_id)->toBeNull();
});

test('new rooms have capacity 2 and are unpublished by default', function () {
    $room = Property::factory()->create()->rooms()->create([
        'number' => '1',
        'facilities' => 'Kasur',
    ]);

    $room->refresh();

    expect($room->capacity)->toBe(2)
        ->and($room->is_published)->toBeFalse();
});

test('room numbers are unique per property', function () {
    $property = Property::factory()->create();
    Room::factory()->for($property)->create(['number' => '1']);

    Room::factory()->for($property)->create(['number' => '1']);
})->throws(UniqueConstraintViolationException::class);

test('the same room number may exist in different properties', function () {
    Room::factory()->create(['number' => '1']);
    Room::factory()->create(['number' => '1']);

    expect(Room::where('number', '1')->count())->toBe(2);
});

test('a property with rooms cannot be deleted', function () {
    $room = Room::factory()->create();

    expect(fn () => $room->property->delete())->toViolateForeignKey();
});

test('an owner with properties cannot be deleted', function () {
    $property = Property::factory()->create();

    expect(fn () => $property->owner->delete())->toViolateForeignKey();
});

test('deleting a room deletes its photos', function () {
    $room = Room::factory()->create();
    RoomPhoto::factory()->for($room)->count(2)->create();

    $room->delete();

    expect(RoomPhoto::count())->toBe(0);
});

test('room photos are ordered by sort order', function () {
    $room = Room::factory()->create();
    RoomPhoto::factory()->for($room)->create(['sort_order' => 2, 'path' => 'rooms/b.jpg']);
    RoomPhoto::factory()->for($room)->create(['sort_order' => 1, 'path' => 'rooms/a.jpg']);

    expect($room->photos->pluck('path')->all())->toBe(['rooms/a.jpg', 'rooms/b.jpg']);
});
