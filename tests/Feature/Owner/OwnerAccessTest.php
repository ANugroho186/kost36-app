<?php

use App\Enums\ComplaintStatus;
use App\Models\Announcement;
use App\Models\Complaint;
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
    // Keluhan yang ia laporkan sendiri: mengubah status atau menghapusnya tetap hak pemilik.
    $complaint = Complaint::factory()->for($occupancy->tenancy->room)->for($tenant, 'reporter')->create();
    $announcement = Announcement::factory()->for($occupancy->tenancy->room->property)->create(['title' => 'Kerja bakti']);

    $url = match ($params) {
        'none' => route($routeName),
        'room' => route($routeName, $room),
        'photo' => route($routeName, [$room, $photo]),
        'tenancy' => route($routeName, $occupancy->tenancy),
        'occupant' => route($routeName, [$occupancy->tenancy, $occupancy]),
        'complaint' => route($routeName, $complaint),
        'announcement' => route($routeName, $announcement),
    };

    $this->actingAs($tenant)->{$method}($url)->assertForbidden();

    expect(Room::find($room->id))->not->toBeNull()
        ->and(RoomPhoto::find($photo->id))->not->toBeNull()
        ->and($complaint->fresh()?->status)->toBe(ComplaintStatus::Reported)
        ->and($announcement->fresh()?->title)->toBe('Kerja bakti');
})->with([
    'buka profil kost' => ['get', 'owner.property.edit', 'none'],
    'simpan profil kost' => ['put', 'owner.property.update', 'none'],
    'daftar kamar' => ['get', 'owner.rooms.index', 'none'],
    'simpan kamar baru' => ['post', 'owner.rooms.store', 'none'],
    'ubah kamar' => ['put', 'owner.rooms.update', 'room'],
    'hapus kamar' => ['delete', 'owner.rooms.destroy', 'room'],
    'unggah foto kamar' => ['post', 'owner.rooms.photos.store', 'room'],
    'jadikan foto sampul' => ['put', 'owner.rooms.photos.cover', 'photo'],
    'hapus foto kamar' => ['delete', 'owner.rooms.photos.destroy', 'photo'],
    'daftar keluhan semua kamar' => ['get', 'owner.complaints.index', 'none'],
    'catat keluhan atas nama penghuni' => ['post', 'owner.complaints.store', 'none'],
    'ubah status keluhan sendiri' => ['put', 'owner.complaints.update', 'complaint'],
    'hapus keluhan sendiri' => ['delete', 'owner.complaints.destroy', 'complaint'],
    'kelola pengumuman' => ['get', 'owner.announcements.index', 'none'],
    'buat pengumuman' => ['post', 'owner.announcements.store', 'none'],
    'ubah pengumuman kost sendiri' => ['put', 'owner.announcements.update', 'announcement'],
    'hapus pengumuman kost sendiri' => ['delete', 'owner.announcements.destroy', 'announcement'],
    'daftar sewa' => ['get', 'owner.tenancies.index', 'none'],
    'simpan sewa baru' => ['post', 'owner.tenancies.store', 'none'],
    'detail sewa sendiri versi pemilik' => ['get', 'owner.tenancies.show', 'tenancy'],
    'tambah penghuni ke kamar sendiri' => ['post', 'owner.tenancies.occupants.store', 'tenancy'],
    'buat akun untuk diri sendiri' => ['post', 'owner.tenancies.occupants.account.store', 'occupant'],
    'reset password sendiri lewat jalur pemilik' => ['put', 'owner.tenancies.occupants.password.reset', 'occupant'],
]);

test('guests are sent to the login page', function () {
    $this->get(route('owner.rooms.index'))->assertRedirect(route('login'));
});

test('the owner can open the owner pages', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->get(route('owner.property.edit'))->assertOk();
    $this->actingAs($owner)->get(route('owner.rooms.index'))->assertOk();
    $this->actingAs($owner)->get(route('owner.tenancies.index'))->assertOk();
    $this->actingAs($owner)->get(route('owner.complaints.index'))->assertOk();
    $this->actingAs($owner)->get(route('owner.announcements.index'))->assertOk();
});
