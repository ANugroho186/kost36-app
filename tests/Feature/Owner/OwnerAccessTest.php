<?php

use App\Models\Room;
use App\Models\RoomPhoto;
use App\Models\TenancyOccupant;
use App\Models\User;

// Setiap route pemilik baru wajib ditambahkan ke dataset ini.
test('tenants cannot open or change owner pages', function (string $method, string $routeName, string $params) {
    $room = Room::factory()->create();
    $photo = RoomPhoto::factory()->for($room)->create();
    // Penghuni sungguhan, bukan sekadar akun tanpa kamar. Untuk route {tenancy}, yang dicoba
    // adalah sewanya sendiri: halaman pemilik tetap tertutup walaupun itu kamarnya.
    $occupancy = TenancyOccupant::factory()->create();
    $tenant = $occupancy->user;

    $url = match ($params) {
        'none' => route($routeName),
        'room' => route($routeName, $room),
        'photo' => route($routeName, [$room, $photo]),
        'tenancy' => route($routeName, $occupancy->tenancy),
    };

    $this->actingAs($tenant)->{$method}($url)->assertForbidden();

    expect(Room::find($room->id))->not->toBeNull()
        ->and(RoomPhoto::find($photo->id))->not->toBeNull();
})->with([
    'buka profil kost' => ['get', 'owner.property.edit', 'none'],
    'simpan profil kost' => ['put', 'owner.property.update', 'none'],
    'daftar kamar' => ['get', 'owner.rooms.index', 'none'],
    'form tambah kamar' => ['get', 'owner.rooms.create', 'none'],
    'simpan kamar baru' => ['post', 'owner.rooms.store', 'none'],
    'form ubah kamar' => ['get', 'owner.rooms.edit', 'room'],
    'ubah kamar' => ['put', 'owner.rooms.update', 'room'],
    'hapus kamar' => ['delete', 'owner.rooms.destroy', 'room'],
    'unggah foto kamar' => ['post', 'owner.rooms.photos.store', 'room'],
    'jadikan foto sampul' => ['put', 'owner.rooms.photos.cover', 'photo'],
    'hapus foto kamar' => ['delete', 'owner.rooms.photos.destroy', 'photo'],
    'daftar sewa' => ['get', 'owner.tenancies.index', 'none'],
    'form sewa baru' => ['get', 'owner.tenancies.create', 'none'],
    'simpan sewa baru' => ['post', 'owner.tenancies.store', 'none'],
    'detail sewa sendiri versi pemilik' => ['get', 'owner.tenancies.show', 'tenancy'],
    'tambah penghuni ke kamar sendiri' => ['post', 'owner.tenancies.occupants.store', 'tenancy'],
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
