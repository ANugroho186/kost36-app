<?php

use App\Models\Room;
use App\Models\RoomPhoto;
use App\Models\User;
use App\Support\ImageUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Storage::fake('public'));

/**
 * JPEG dengan segmen EXIF minimal yang hanya berisi tag Orientation, seperti yang ditulis
 * kamera HP. Orientation 6 artinya "putar 90° searah jarum jam saat ditampilkan".
 */
function jpegWithOrientation(int $width, int $height, int $orientation): UploadedFile
{
    ob_start();
    imagejpeg(imagecreatetruecolor($width, $height));
    $jpeg = (string) ob_get_clean();

    // Header TIFF little-endian, satu entri IFD: tag 0x0112 (Orientation), tipe SHORT, 1 nilai.
    $tiff = 'II*'."\0".pack('V', 8).pack('v', 1).pack('vvVvv', 0x0112, 3, 1, $orientation, 0).pack('V', 0);
    $payload = "Exif\0\0".$tiff;
    $app1 = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

    $path = tempnam(sys_get_temp_dir(), 'exif');
    file_put_contents($path, substr($jpeg, 0, 2).$app1.substr($jpeg, 2));

    return new UploadedFile($path, 'foto-dari-hp.jpg', 'image/jpeg', null, true);
}

test('uploaded photos are re-encoded as webp with a random name', function () {
    $room = Room::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.rooms.photos.store', $room), [
            'photos' => [
                UploadedFile::fake()->image('kamar-saya.jpg', 2400, 1200),
                UploadedFile::fake()->image('kamar-saya-2.png', 800, 600),
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.rooms.edit', $room));

    $photos = $room->photos()->get();

    expect($photos)->toHaveCount(2)
        ->and($photos->pluck('sort_order')->all())->toBe([0, 1]);

    foreach ($photos as $photo) {
        expect($photo->path)->toStartWith('rooms/')->toEndWith('.webp')->not->toContain('kamar');
        Storage::disk('public')->assertExists($photo->path);
    }

    // Sisi terpanjang diperkecil menjadi 1600 piksel, perbandingan sisi tetap.
    $size = getimagesizefromstring(Storage::disk('public')->get($photos[0]->path));
    expect([$size[0], $size[1]])->toBe([ImageUpload::MAX_SIDE, 800])
        ->and($size['mime'])->toBe('image/webp');
});

test('camera orientation is applied and the metadata is removed', function () {
    $room = Room::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.rooms.photos.store', $room), ['photos' => [jpegWithOrientation(200, 100, 6)]])
        ->assertSessionHasNoErrors();

    $stored = Storage::disk('public')->get($room->photos()->sole()->path);
    $size = getimagesizefromstring($stored);

    expect([$size[0], $size[1]])->toBe([100, 200])
        ->and($stored)->not->toContain('Exif');
});

test('only images within the size limits are accepted', function (UploadedFile $file) {
    $room = Room::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.rooms.edit', $room))
        ->post(route('owner.rooms.photos.store', $room), ['photos' => [$file]])
        ->assertSessionHasErrors('photos.0');

    expect($room->photos()->count())->toBe(0);
})->with([
    'dokumen pdf' => fn () => UploadedFile::fake()->create('denah.pdf', 100, 'application/pdf'),
    'lebih dari 5 MB' => fn () => UploadedFile::fake()->image('besar.jpg')->size(6000),
    'lebih dari 6000 piksel' => fn () => UploadedFile::fake()->image('raksasa.png', 6001, 10),
]);

test('oversized photos explain how to fix them', function (UploadedFile $file) {
    $room = Room::factory()->create();

    // assertInvalid memeriksa bahwa pesan error untuk photos.0 mengandung teks ini.
    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.rooms.edit', $room))
        ->post(route('owner.rooms.photos.store', $room), ['photos' => [$file]])
        ->assertInvalid(['photos.0' => 'normal mode']);
})->with([
    'ukuran file' => fn () => UploadedFile::fake()->image('50mp.jpg')->size(10000),
    'ukuran piksel' => fn () => UploadedFile::fake()->image('50mp.png', 6001, 10),
]);

test('a room can have at most eight photos', function () {
    $room = Room::factory()->create();
    RoomPhoto::factory()->for($room)->count(8)->create();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.rooms.edit', $room))
        ->post(route('owner.rooms.photos.store', $room), ['photos' => [UploadedFile::fake()->image('ke-9.jpg')]])
        ->assertSessionHasErrors('photos');

    expect($room->photos()->count())->toBe(8);
});

test('the owner chooses the cover photo without shuffling the others', function () {
    $room = Room::factory()->create();
    [$first, $second, $third] = collect([0, 1, 2])
        ->map(fn (int $order) => RoomPhoto::factory()->for($room)->create(['sort_order' => $order]))
        ->all();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.rooms.photos.cover', [$room, $third]))
        ->assertRedirect(route('owner.rooms.edit', $room));

    expect($room->photos()->pluck('id')->all())->toBe([$third->id, $first->id, $second->id]);
});

test('deleting a photo also deletes its file', function () {
    Storage::disk('public')->put('rooms/contoh.webp', 'gambar');
    $room = Room::factory()->create();
    $photo = RoomPhoto::factory()->for($room)->create(['path' => 'rooms/contoh.webp']);

    $this->actingAs(User::factory()->owner()->create())
        ->delete(route('owner.rooms.photos.destroy', [$room, $photo]))
        ->assertRedirect(route('owner.rooms.edit', $room));

    expect(RoomPhoto::find($photo->id))->toBeNull();
    Storage::disk('public')->assertMissing('rooms/contoh.webp');
});

test('a photo cannot be changed through another room in the URL', function () {
    $room = Room::factory()->create();
    $photoOfAnotherRoom = RoomPhoto::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->delete(route('owner.rooms.photos.destroy', [$room, $photoOfAnotherRoom]))
        ->assertNotFound();

    expect(RoomPhoto::find($photoOfAnotherRoom->id))->not->toBeNull();
});

test('the cover photo is the first photo on the public page', function () {
    $room = Room::factory()->published()->create();
    RoomPhoto::factory()->for($room)->create(['path' => 'rooms/kedua.webp', 'sort_order' => 1]);
    RoomPhoto::factory()->for($room)->create(['path' => 'rooms/sampul.webp', 'sort_order' => 0]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rooms.0.photos.0', Storage::disk('public')->url('rooms/sampul.webp')),
        );
});
