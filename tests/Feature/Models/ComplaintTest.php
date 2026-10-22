<?php

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\ComplaintPhoto;
use App\Models\MaintenanceLog;
use App\Models\Room;

test('new complaints are reported by default', function () {
    $complaint = Room::factory()->create()->complaints()->create([
        'title' => 'Keran bocor',
        'description' => 'Air menetes terus.',
    ]);

    expect($complaint->fresh()->status)->toBe(ComplaintStatus::Reported);
});

test('complaint status and reporter cannot be mass assigned', function () {
    $complaint = new Complaint([
        'status' => ComplaintStatus::Resolved->value,
        'reporter_id' => 1,
    ]);

    expect($complaint->status)->toBe(ComplaintStatus::Reported)
        ->and($complaint->reporter_id)->toBeNull();
});

test('deleting the reporter keeps the complaint', function () {
    $complaint = Complaint::factory()->create();

    $complaint->reporter->delete();

    expect($complaint->fresh()->reporter_id)->toBeNull();
});

test('a room with complaints cannot be deleted', function () {
    $complaint = Complaint::factory()->create();

    expect(fn () => $complaint->room->delete())->toViolateForeignKey();
});

test('deleting a complaint deletes its photos', function () {
    $complaint = Complaint::factory()->create();
    ComplaintPhoto::factory()->for($complaint)->count(2)->create();

    $complaint->delete();

    expect(ComplaintPhoto::count())->toBe(0);
});

test('complaint photo path is hidden when serialized', function () {
    $photo = ComplaintPhoto::factory()->create();

    expect($photo->toArray())->not->toHaveKey('path');
});

test('deleting a complaint keeps its maintenance log', function () {
    $complaint = Complaint::factory()->create();
    $log = MaintenanceLog::factory()->forRoom($complaint->room)->create([
        'complaint_id' => $complaint->id,
    ]);

    $complaint->delete();

    expect($log->fresh()->complaint_id)->toBeNull()
        ->and($log->fresh()->room_id)->toBe($complaint->room_id);
});
