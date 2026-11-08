<?php

use App\Models\Complaint;
use App\Models\Parcel;
use App\Models\ParcelReport;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Models\User;
use App\Notifications\ComplaintReported;
use App\Notifications\ComplaintResolved;
use App\Notifications\ParcelHeldByOwner;
use App\Notifications\ParcelMisdelivered;
use App\Notifications\ParcelRegistered;
use App\Notifications\ParcelReportReceived;
use App\Notifications\PortalNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Isi `message` notifikasi untuk penerima tertentu, untuk memeriksa apa yang tertulis di dalamnya.
 */
function notificationMessage(PortalNotification $notification, User $notifiable): string
{
    return $notification->toArray($notifiable)['message'];
}

// Keluhan.

test('a complaint from a tenant notifies the property owner', function () {
    Notification::fake();
    $scenario = accessScenario();
    $room = $scenario['tenancy']->room;

    $this->actingAs($scenario['occupant'])
        ->post(route('complaints.store'), ['title' => 'Keran bocor', 'description' => 'Menetes terus.'])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo(
        $room->property->owner,
        ComplaintReported::class,
        fn (ComplaintReported $notification, array $channels, User $owner) => str_contains(notificationMessage($notification, $owner), "room {$room->number}")
            && str_contains(notificationMessage($notification, $owner), 'Keran bocor'),
    );
    Notification::assertNotSentTo([$scenario['roommate'], $scenario['otherTenant']], ComplaintReported::class);
});

test('a complaint the owner records does not notify the owner', function () {
    Notification::fake();
    $scenario = accessScenario();

    $this->actingAs($scenario['owner'])
        ->post(route('owner.complaints.store'), [
            'room_id' => $scenario['tenancy']->room_id,
            'title' => 'Keran bocor',
            'description' => 'Diceritakan langsung.',
        ])
        ->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

test('resolving a complaint notifies the people of that room who can see it', function () {
    Notification::fake();
    $scenario = accessScenario();
    $complaint = Complaint::factory()->for($scenario['tenancy']->room)->for($scenario['occupant'], 'reporter')->inProgress()->create();

    $this->actingAs($scenario['owner'])
        ->put(route('owner.complaints.update', $complaint), ['status' => 'resolved'])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo([$scenario['occupant'], $scenario['roommate']], ComplaintResolved::class);
    Notification::assertNotSentTo([$scenario['otherTenant'], $scenario['formerOccupant'], $scenario['owner']], ComplaintResolved::class);
});

test('people living in a room are not told about complaints from before their time', function () {
    Notification::fake();
    $scenario = accessScenario();
    $oldComplaint = Complaint::factory()->for($scenario['tenancy']->room)->create([
        'created_at' => $scenario['tenancy']->start_date->subDay(),
    ]);

    $this->actingAs($scenario['owner'])->put(route('owner.complaints.update', $oldComplaint), ['status' => 'resolved']);

    Notification::assertNothingSent();
});

test('only a fresh resolution notifies, not saving or reopening', function (string $from, string $to) {
    Notification::fake();
    $scenario = accessScenario();
    $complaint = Complaint::factory()->for($scenario['tenancy']->room)->{$from}()->create();

    $this->actingAs($scenario['owner'])->put(route('owner.complaints.update', $complaint), ['status' => $to]);

    Notification::assertNothingSent();
})->with([
    'disimpan ulang setelah selesai' => ['resolved', 'resolved'],
    'mulai dikerjakan' => ['inProgress', 'in_progress'],
]);

// Paket.

test('registering a parcel tells the owner who and which room, never what is inside', function () {
    $scenario = accessScenario();
    $occupancy = $scenario['occupant']->currentOccupancy()->with('tenancy.room')->first();
    $owner = $scenario['tenancy']->room->property->owner;

    $this->actingAs($scenario['occupant'])
        ->post(route('parcels.store'), ['tracking_number' => 'JNE123', 'item_name' => 'Obat pribadi'])
        ->assertSessionHasNoErrors();

    $stored = $owner->notifications()->sole();

    expect($stored->type)->toBe(ParcelRegistered::class)
        ->and($stored->data['message'])->toContain($occupancy->nickname)
        ->toContain("room {$occupancy->tenancy->room->number}")
        ->toContain('JNE123')
        ->and(json_encode($stored->data))->not->toContain('Obat pribadi');
});

test('the owner holding a parcel tells only its owner, without what is inside', function () {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create(['tracking_number' => 'JNE123', 'item_name' => 'Obat pribadi']);

    $this->actingAs($scenario['owner'])->put(route('owner.parcels.held', $parcel))->assertRedirect();

    $stored = $scenario['occupant']->notifications()->sole();

    expect($stored->type)->toBe(ParcelHeldByOwner::class)
        ->and($stored->data['message'])->toContain('JNE123')
        ->and(json_encode($stored->data))->not->toContain('Obat pribadi')
        ->and($scenario['roommate']->notifications()->count())->toBe(0);
});

// Laporan salah terima.

test('a wrong-recipient report notifies the property owner', function () {
    Notification::fake();
    $scenario = accessScenario();

    $this->actingAs($scenario['occupant'])
        ->post(route('parcel-reports.store'), ['description' => 'Paket resi JNE123.'])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($scenario['tenancy']->room->property->owner, ParcelReportReceived::class);
    Notification::assertNotSentTo($scenario['occupant'], ParcelReportReceived::class);
});

test('matching a report tells the parcel owner, without who reported it', function () {
    $scenario = accessScenario();
    $reporter = TenancyOccupant::factory()->for(
        Tenancy::factory()->for(Room::factory()->for($scenario['tenancy']->room->property)),
    )->create(['nickname' => 'Citra'])->user;
    $report = ParcelReport::factory()->for($reporter, 'reporter')->create(['description' => 'Paket untuk Budi ada di kamar saya.']);
    $parcel = Parcel::factory()->for($scenario['occupant'])->create(['tracking_number' => 'JNE123']);

    $this->actingAs($scenario['owner'])
        ->put(route('owner.parcel-reports.link', $report), ['parcel_id' => $parcel->id])
        ->assertSessionHasNoErrors();

    $stored = $scenario['occupant']->notifications()->sole();

    expect($stored->type)->toBe(ParcelMisdelivered::class)
        ->and($stored->data['message'])->toContain('JNE123')
        ->and(json_encode($stored->data))->not->toContain('Citra')->not->toContain('ada di kamar saya')
        ->and($reporter->notifications()->count())->toBe(0);
});

test('saving the same match again does not notify twice', function () {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create();
    $report = ParcelReport::factory()->create(['parcel_id' => $parcel->id]);

    $this->actingAs($scenario['owner'])->put(route('owner.parcel-reports.link', $report), ['parcel_id' => $parcel->id]);

    expect($scenario['occupant']->notifications()->count())->toBe(0);
});
