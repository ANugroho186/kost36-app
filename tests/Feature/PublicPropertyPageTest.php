<?php

use App\Models\Property;
use App\Models\Room;
use App\Models\Tenancy;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('the public page renders before any property exists', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->where('property', null)
            ->where('rooms', [])
            ->where('summary', ['available' => 0, 'total' => 0]),
        );
});

test('the public page lists published rooms with their availability', function () {
    $property = Property::factory()->create(['name' => 'Kost Contoh']);
    $occupied = Room::factory()->for($property)->published()->create(['number' => '1']);
    $available = Room::factory()->for($property)->published()->create(['number' => '2']);
    $inactive = Room::factory()->for($property)->published()->create(['number' => '3']);
    Room::factory()->for($property)->create(['number' => '4']);

    Tenancy::factory()->for($occupied)->create();
    Tenancy::factory()->for($available)->ended()->create();
    Tenancy::factory()->for($inactive)->inactive()->create();

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('property.name', 'Kost Contoh')
            ->has('rooms', 3)
            ->where('rooms.0.number', '1')
            ->where('rooms.0.isAvailable', false)
            ->where('rooms.1.number', '2')
            ->where('rooms.1.isAvailable', true)
            ->where('rooms.2.number', '3')
            ->where('rooms.2.isAvailable', false)
            ->where('summary', ['available' => 1, 'total' => 3]),
        );
});

test('rooms are listed in natural number order', function () {
    $property = Property::factory()->create();
    foreach (['10', '2', '1'] as $number) {
        Room::factory()->for($property)->published()->create(['number' => $number]);
    }

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rooms.0.number', '1')
            ->where('rooms.1.number', '2')
            ->where('rooms.2.number', '10'),
        );
});

test('the public page only sends the fields it needs', function () {
    $room = Room::factory()->published()->create();
    Tenancy::factory()->for($room)->create();

    // Tanpa ->etc(), Inertia menolak kunci tambahan apa pun: daftar ini adalah daftar putih.
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('property', fn (Assert $property) => $property
                ->has('name')
                ->has('address')
                ->has('description')
                ->has('rules'),
            )
            ->has('rooms.0', fn (Assert $room) => $room
                ->has('number')
                ->has('capacity')
                ->has('facilities')
                ->has('isAvailable')
                ->has('photos'),
            ),
        );
});

test('the public page never exposes tenant identities', function () {
    $this->seed(DemoSeeder::class);

    $props = json_encode($this->get(route('home'))->viewData('page')['props']);

    foreach (['Budi', 'Andi', 'Citra', 'Dodi', 'Eko', 'penghuni'] as $identity) {
        expect($props)->not->toContain($identity);
    }
});
