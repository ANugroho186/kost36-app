<?php

use App\Models\Room;
use App\Models\TenancyOccupant;
use App\Models\User;

test('tenants cannot open or change owner pages', function (string $method, string $routeName, bool $needsRoom) {
    $room = Room::factory()->create();
    // Penghuni sungguhan yang tinggal di kamar itu, bukan sekadar akun tanpa kamar.
    $tenant = TenancyOccupant::factory()->create()->user;
    $url = $needsRoom ? route($routeName, $room) : route($routeName);

    $this->actingAs($tenant)->{$method}($url)->assertForbidden();

    expect(Room::find($room->id))->not->toBeNull();
})->with([
    'buka profil kost' => ['get', 'owner.property.edit', false],
    'simpan profil kost' => ['put', 'owner.property.update', false],
    'daftar kamar' => ['get', 'owner.rooms.index', false],
    'form tambah kamar' => ['get', 'owner.rooms.create', false],
    'simpan kamar baru' => ['post', 'owner.rooms.store', false],
    'form ubah kamar' => ['get', 'owner.rooms.edit', true],
    'ubah kamar' => ['put', 'owner.rooms.update', true],
    'hapus kamar' => ['delete', 'owner.rooms.destroy', true],
]);

test('guests are sent to the login page', function () {
    $this->get(route('owner.rooms.index'))->assertRedirect(route('login'));
});

test('the owner can open the owner pages', function () {
    $owner = User::factory()->owner()->create();
    $room = Room::factory()->create();

    $this->actingAs($owner)->get(route('owner.property.edit'))->assertOk();
    $this->actingAs($owner)->get(route('owner.rooms.index'))->assertOk();
    $this->actingAs($owner)->get(route('owner.rooms.create'))->assertOk();
    $this->actingAs($owner)->get(route('owner.rooms.edit', $room))->assertOk();
});
