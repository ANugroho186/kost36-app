<?php

use App\Enums\InventoryCondition;
use App\Enums\RentalInquiryStatus;
use App\Models\Announcement;
use App\Models\InventoryItem;
use App\Models\MaintenanceLog;
use App\Models\Property;
use App\Models\RentalInquiry;
use App\Models\Room;

test('maintenance and inventory without a room belong to common areas', function () {
    $property = Property::factory()->create();
    MaintenanceLog::factory()->for($property)->create();
    InventoryItem::factory()->for($property)->create();

    expect($property->maintenanceLogs()->whereNull('room_id')->count())->toBe(1)
        ->and($property->inventoryItems()->whereNull('room_id')->count())->toBe(1);
});

test('a room with maintenance history cannot be deleted', function () {
    $room = Room::factory()->create();
    MaintenanceLog::factory()->forRoom($room)->create();

    expect(fn () => $room->delete())->toViolateForeignKey();
});

test('a room with inventory items cannot be deleted', function () {
    $room = Room::factory()->create();
    InventoryItem::factory()->forRoom($room)->create();

    expect(fn () => $room->delete())->toViolateForeignKey();
});

test('a property with maintenance history cannot be deleted', function () {
    $log = MaintenanceLog::factory()->create();

    expect(fn () => $log->property->delete())->toViolateForeignKey();
});

test('inventory condition is cast to an enum', function () {
    $item = InventoryItem::factory()->needsRepair()->create();

    expect($item->fresh()->condition)->toBe(InventoryCondition::NeedsRepair);
});

test('public rental inquiries cannot set their own status', function () {
    $inquiry = Property::factory()->create()->rentalInquiries()->create([
        'name' => 'Calon Penyewa',
        'contact' => 'calon@example.com',
        'status' => RentalInquiryStatus::Done->value,
        'done_at' => now(),
    ]);

    expect($inquiry->fresh()->status)->toBe(RentalInquiryStatus::New)
        ->and($inquiry->fresh()->done_at)->toBeNull();
});

test('deleting a property without rooms deletes its announcements, inventory, and inquiries', function () {
    $property = Property::factory()->create();
    Announcement::factory()->for($property)->create();
    InventoryItem::factory()->for($property)->create();
    RentalInquiry::factory()->for($property)->create();

    $property->delete();

    expect(Announcement::count() + InventoryItem::count() + RentalInquiry::count())->toBe(0);
});
