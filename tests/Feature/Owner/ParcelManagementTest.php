<?php

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use App\Models\TenancyOccupant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('the owner sees every parcel with its recipient and room, open ones by expected date', function () {
    $occupancy = TenancyOccupant::factory()->create(['nickname' => 'Budi']);
    $tenant = $occupancy->user;
    $later = Parcel::factory()->for($tenant)->create(['expected_date' => '2026-11-12']);
    $sooner = Parcel::factory()->for($tenant)->atOwner()->create(['expected_date' => '2026-11-08']);
    $undated = Parcel::factory()->for($tenant)->create(['expected_date' => null]);
    $received = Parcel::factory()->for($tenant)->receivedDirectly()->create(['expected_date' => '2026-11-01']);

    $response = $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.parcels.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('owner/parcels/index')
            ->where('parcels.0.recipient', 'Budi')
            ->where('parcels.0.roomNumber', $occupancy->tenancy->room->number),
        );

    expect(array_column($response->inertiaProps('parcels'), 'id'))
        ->toBe([$sooner->id, $later->id, $undated->id, $received->id]);
});

test('each parcel carries only what the owner may see, never the item name', function () {
    $tenant = TenancyOccupant::factory()->create(['nickname' => 'Budi'])->user;
    $parcel = Parcel::factory()->for($tenant)->create([
        'tracking_number' => 'JNE123',
        'item_name' => 'Obat pribadi',
        'expected_date' => '2026-11-10',
    ]);

    // Tanpa ->etc(): kunci tambahan (misalnya itemName) membuat test gagal.
    $response = $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.parcels.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('parcels', 1)
            ->has('parcels.0', fn (Assert $item) => $item
                ->where('id', $parcel->id)
                ->where('trackingNumber', 'JNE123')
                ->where('recipient', 'Budi')
                ->has('roomNumber')
                ->where('expectedDate', '2026-11-10')
                ->where('status', 'waiting'),
            ),
        );

    // Isi barang tidak muncul di mana pun pada halaman, termasuk di data halaman Inertia.
    $response->assertDontSee('Obat pribadi');
});

test('the owner page does not even read the item name from the database', function () {
    Parcel::factory()->create(['item_name' => 'Obat pribadi']);
    $owner = User::factory()->owner()->create();

    DB::enableQueryLog();
    $this->actingAs($owner)->get(route('owner.parcels.index'))->assertOk();

    $parcelQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $sql) => str_contains($sql, 'from "parcels"'))
        ->all();

    expect($parcelQueries)->not->toBeEmpty();

    // Kolom dipilih satu per satu (bukan select *), dan item_name tidak termasuk.
    foreach ($parcelQueries as $sql) {
        expect($sql)->not->toContain('*')->not->toContain('item_name');
    }
});

test('the demo owner never receives the demo item names', function () {
    $this->seed(DemoSeeder::class);

    $response = $this->actingAs(User::firstWhere('username', 'pemilik'))->get(route('owner.parcels.index'));

    foreach (Parcel::all() as $parcel) {
        $response->assertSee($parcel->tracking_number);
        $response->assertDontSee($parcel->item_name);
    }
});

test('the owner marks a waiting parcel as being at their place', function () {
    $parcel = Parcel::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.parcels.held', $parcel))
        ->assertRedirect(route('owner.parcels.index'));

    expect($parcel->fresh()->status)->toBe(ParcelStatus::AtOwner);
});

test('a parcel that someone already received cannot be marked again', function (string $state) {
    $parcel = Parcel::factory()->{$state}()->create();
    $before = $parcel->held_by_owner_at;

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.parcels.held', $parcel))
        ->assertForbidden();

    expect($parcel->fresh()->held_by_owner_at?->toDateTimeString())->toBe($before?->toDateTimeString());
})->with(['atOwner', 'pickedUp', 'receivedDirectly']);
