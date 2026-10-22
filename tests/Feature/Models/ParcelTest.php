<?php

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use App\Models\ParcelReport;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

test('item name is stored encrypted in the database', function () {
    $parcel = Parcel::factory()->create(['item_name' => 'Sepatu lari']);

    // Dibaca langsung dari tabel, tanpa melewati cast model.
    $stored = DB::table('parcels')->where('id', $parcel->id)->value('item_name');

    expect($stored)->not->toContain('Sepatu')
        ->and(Crypt::decryptString($stored))->toBe('Sepatu lari')
        ->and($parcel->fresh()->item_name)->toBe('Sepatu lari');
});

test('item name is hidden when serialized', function () {
    $parcel = Parcel::factory()->create();

    expect($parcel->toArray())->not->toHaveKey('item_name')
        ->and($parcel->toArray())->toHaveKey('tracking_number');
});

test('item name is only serialized when explicitly requested', function () {
    $parcel = Parcel::factory()->create(['item_name' => 'Buku']);

    expect($parcel->makeVisible('item_name')->toArray()['item_name'])->toBe('Buku');
});

test('tenants register parcels without touching ownership or handover times', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $parcel = $user->parcels()->create([
        'tracking_number' => 'JP0000000001',
        'user_id' => $other->id,
        'held_by_owner_at' => now(),
        'collected_at' => now(),
    ]);

    $parcel->refresh();

    expect($parcel->user_id)->toBe($user->id)
        ->and($parcel->held_by_owner_at)->toBeNull()
        ->and($parcel->collected_at)->toBeNull()
        ->and($parcel->status)->toBe(ParcelStatus::Waiting);
});

test('parcel status is derived from its handover times', function (array $times, ParcelStatus $expected) {
    $parcel = (new Parcel)->forceFill($times);

    expect($parcel->status)->toBe($expected);
})->with([
    'menunggu' => [[], ParcelStatus::Waiting],
    'di rumah pemilik' => [['held_by_owner_at' => '2026-10-20 10:00:00'], ParcelStatus::AtOwner],
    'sudah diambil dari pemilik' => [['held_by_owner_at' => '2026-10-20 10:00:00', 'collected_at' => '2026-10-21 18:00:00'], ParcelStatus::PickedUp],
    'diterima langsung' => [['collected_at' => '2026-10-21 18:00:00'], ParcelStatus::ReceivedDirectly],
]);

test('a tenant cannot register the same tracking number twice', function () {
    $user = User::factory()->create();
    Parcel::factory()->for($user)->create(['tracking_number' => 'JP0000000001']);

    Parcel::factory()->for($user)->create(['tracking_number' => 'JP0000000001']);
})->throws(UniqueConstraintViolationException::class);

test('different tenants may register the same tracking number', function () {
    Parcel::factory()->create(['tracking_number' => 'JP0000000001']);
    Parcel::factory()->create(['tracking_number' => 'JP0000000001']);

    expect(Parcel::where('tracking_number', 'JP0000000001')->count())->toBe(2);
});

test('deleting an account deletes its parcels', function () {
    $parcel = Parcel::factory()->create();

    $parcel->user->delete();

    expect(Parcel::count())->toBe(0);
});

test('reporters cannot link their report to a parcel themselves', function () {
    $reporter = User::factory()->create();
    $parcel = Parcel::factory()->create();

    $report = $reporter->parcelReports()->create([
        'description' => 'Saya menerima paket yang bukan milik saya.',
        'parcel_id' => $parcel->id,
        'resolved_at' => now(),
    ]);

    $report->refresh();

    expect($report->parcel_id)->toBeNull()
        ->and($report->resolved_at)->toBeNull()
        ->and($report->reporter_id)->toBe($reporter->id);
});

test('reports remain after the parcel or the reporter is deleted', function () {
    $report = ParcelReport::factory()->for(Parcel::factory())->create();

    $report->parcel->delete();
    $report->reporter->delete();

    expect($report->fresh()->parcel_id)->toBeNull()
        ->and($report->fresh()->reporter_id)->toBeNull();
});
