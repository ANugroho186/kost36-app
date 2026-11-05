<?php

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('tenants see only their own parcels, not yet received ones first', function () {
    $scenario = accessScenario();
    $received = Parcel::factory()->for($scenario['occupant'])->receivedDirectly()->create(['created_at' => now()->subHour()]);
    $older = Parcel::factory()->for($scenario['occupant'])->create(['created_at' => now()->subDays(2)]);
    $newer = Parcel::factory()->for($scenario['occupant'])->atOwner()->create(['created_at' => now()->subDay()]);
    // Paket teman sekamar dan tetangga tidak boleh ikut, walaupun satu kamar.
    Parcel::factory()->for($scenario['roommate'])->create();
    Parcel::factory()->for($scenario['otherTenant'])->create();

    $listed = $this->actingAs($scenario['occupant'])->get(route('parcels.index'))->assertOk()->inertiaProps('parcels');

    expect(array_column($listed, 'id'))->toBe([$newer->id, $older->id, $received->id]);
});

test('the parcel list only sends what the page needs', function () {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create([
        'tracking_number' => 'JNE123',
        'item_name' => 'Charger HP',
        'expected_date' => '2026-11-10',
    ]);

    // Tanpa ->etc(): kunci tambahan membuat test gagal.
    $this->actingAs($scenario['occupant'])
        ->get(route('parcels.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('tenant/parcels/index')
            ->where('canRegister', true)
            ->has('parcels.0', fn (Assert $item) => $item
                ->where('id', $parcel->id)
                ->where('trackingNumber', 'JNE123')
                ->where('itemName', 'Charger HP')
                ->where('expectedDate', '2026-11-10')
                ->where('status', 'waiting'),
            ),
        );
});

test('a tenant registers a parcel with a tidied tracking number and an encrypted item name', function () {
    $scenario = accessScenario();

    $this->actingAs($scenario['occupant'])
        ->post(route('parcels.store'), [
            'tracking_number' => ' jne 123 abc ',
            'item_name' => 'Charger HP',
            'expected_date' => '2026-11-10',
            // Diabaikan: pemilik paket dan waktu serah terima tidak bisa diisi dari form.
            'user_id' => $scenario['roommate']->id,
            'held_by_owner_at' => now()->toDateTimeString(),
            'collected_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('parcels.index'));

    $parcel = Parcel::query()->sole();

    expect($parcel->user_id)->toBe($scenario['occupant']->id)
        ->and($parcel->tracking_number)->toBe('JNE123ABC')
        ->and($parcel->item_name)->toBe('Charger HP')
        ->and($parcel->status)->toBe(ParcelStatus::Waiting)
        // Isi kolom di database terenkripsi, bukan teks asli.
        ->and(DB::table('parcels')->value('item_name'))->not->toContain('Charger');
});

test('a tenant cannot register the same tracking number twice, however it is written', function () {
    $scenario = accessScenario();
    Parcel::factory()->for($scenario['occupant'])->create(['tracking_number' => 'JNE123']);

    $this->actingAs($scenario['occupant'])
        ->post(route('parcels.store'), ['tracking_number' => 'jne 123'])
        ->assertInvalid(['tracking_number' => 'already registered']);

    expect(Parcel::query()->count())->toBe(1);
});

test('another tenant may register the same tracking number without learning it exists', function () {
    $scenario = accessScenario();
    Parcel::factory()->for($scenario['roommate'])->create(['tracking_number' => 'JNE123']);

    $this->actingAs($scenario['occupant'])
        ->post(route('parcels.store'), ['tracking_number' => 'JNE123'])
        ->assertSessionHasNoErrors();

    expect(Parcel::query()->count())->toBe(2);
});

test('parcels are validated', function (array $input, string $field) {
    $this->actingAs(accessScenario()['occupant'])
        ->post(route('parcels.store'), ['tracking_number' => 'JNE123', ...$input])
        ->assertInvalid($field);

    expect(Parcel::query()->count())->toBe(0);
})->with([
    'tanpa resi' => [['tracking_number' => '   '], 'tracking_number'],
    'resi dengan simbol' => [['tracking_number' => 'JNE#123'], 'tracking_number'],
    'resi terlalu panjang' => [['tracking_number' => str_repeat('A', 51)], 'tracking_number'],
    'nama barang terlalu panjang' => [['item_name' => str_repeat('a', 101)], 'item_name'],
    'tanggal tidak valid' => [['expected_date' => 'besok'], 'expected_date'],
]);

test('only people living in a room can register parcels', function (string $actor) {
    $scenario = accessScenario();

    // Data kosong tetap 403, bukan error validasi: hak akses dicek lebih dulu.
    $this->actingAs($scenario[$actor])->post(route('parcels.store'), [])->assertForbidden();

    $this->actingAs($scenario[$actor])
        ->get(route('parcels.index'))
        ->assertInertia(fn (Assert $page) => $page->where('canRegister', false));
})->with([
    'pemilik kost' => ['owner'],
    'penghuni yang sudah keluar' => ['formerOccupant'],
]);

test('a tenant edits a parcel while it is on the way and may keep its number', function () {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create(['tracking_number' => 'JNE123']);

    $this->actingAs($scenario['occupant'])
        ->put(route('parcels.update', $parcel), ['tracking_number' => 'JNE123', 'item_name' => 'Kaos'])
        ->assertSessionHasNoErrors();

    expect($parcel->fresh()->item_name)->toBe('Kaos');
});

test('a tenant cancels a parcel while it is on the way', function () {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create();

    $this->actingAs($scenario['occupant'])
        ->delete(route('parcels.destroy', $parcel))
        ->assertRedirect(route('parcels.index'));

    expect(Parcel::find($parcel->id))->toBeNull();
});

test('marking a parcel as received depends on who handed it over', function (?string $state, ParcelStatus $expected) {
    $scenario = accessScenario();
    $factory = Parcel::factory()->for($scenario['occupant']);
    $parcel = ($state === null ? $factory : $factory->{$state}())->create();

    $this->actingAs($scenario['occupant'])
        ->put(route('parcels.collected', $parcel))
        ->assertRedirect(route('parcels.index'));

    expect($parcel->fresh()->status)->toBe($expected);
})->with([
    'langsung dari kurir' => [null, ParcelStatus::ReceivedDirectly],
    'diambil dari pemilik kost' => ['atOwner', ParcelStatus::PickedUp],
]);

test('a parcel cannot be changed once someone has received it', function (string $state) {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->{$state}()->create(['tracking_number' => 'JNE123']);

    $this->actingAs($scenario['occupant'])->put(route('parcels.update', $parcel), ['tracking_number' => 'JNE999'])->assertForbidden();
    $this->actingAs($scenario['occupant'])->delete(route('parcels.destroy', $parcel))->assertForbidden();

    expect($parcel->fresh()->tracking_number)->toBe('JNE123');
})->with(['atOwner', 'pickedUp', 'receivedDirectly']);

test('nobody else can touch a parcel through its id', function (string $actor, string $method, string $route) {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create(['tracking_number' => 'JNE123']);

    $this->actingAs($scenario[$actor])
        ->{$method}(route($route, $parcel), ['tracking_number' => 'JNE999'])
        ->assertForbidden();

    expect($parcel->fresh())
        ->tracking_number->toBe('JNE123')
        ->status->toBe(ParcelStatus::Waiting);
})->with([
    'teman sekamar' => ['roommate'],
    'tetangga kamar lain' => ['otherTenant'],
    // Pemilik kost hanya boleh mencentang "di rumah saya" (langkah 3b), tidak mengubah paket.
    'pemilik kost' => ['owner'],
])->with([
    'ubah' => ['put', 'parcels.update'],
    'batalkan' => ['delete', 'parcels.destroy'],
    'centang diterima' => ['put', 'parcels.collected'],
]);

test('guests are sent to the login page', function () {
    $this->get(route('parcels.index'))->assertRedirect(route('login'));
});
