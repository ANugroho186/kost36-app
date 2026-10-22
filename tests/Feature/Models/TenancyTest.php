<?php

use App\Enums\TenancyStatus;
use App\Models\Room;
use App\Models\Tenancy;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

test('new tenancies are active by default', function () {
    $tenancy = Room::factory()->create()->tenancies()->create([
        'start_date' => '2026-01-01',
        'paid_until' => '2026-02-01',
    ]);

    expect($tenancy->fresh()->status)->toBe(TenancyStatus::Active);
});

test('tenancy status cannot be mass assigned', function () {
    $tenancy = new Tenancy(['status' => TenancyStatus::Ended->value]);

    expect($tenancy->status)->toBe(TenancyStatus::Active);
});

test('tenancy dates are immutable', function () {
    $tenancy = Tenancy::factory()->create(['paid_until' => '2026-02-01']);

    $next = $tenancy->paid_until->addMonthsNoOverflow(1);

    expect($tenancy->paid_until)->toBeInstanceOf(CarbonImmutable::class)
        ->and($tenancy->paid_until->toDateString())->toBe('2026-02-01')
        ->and($next->toDateString())->toBe('2026-03-01');
});

test('a room can have only one occupying tenancy', function () {
    $room = Room::factory()->create();
    Tenancy::factory()->for($room)->create();

    Tenancy::factory()->for($room)->inactive()->create();
})->throws(UniqueConstraintViolationException::class);

test('ended tenancies do not block a new tenancy in the same room', function () {
    $room = Room::factory()->create();
    Tenancy::factory()->for($room)->ended()->count(2)->create();

    Tenancy::factory()->for($room)->create();

    expect($room->tenancies()->count())->toBe(3);
});

test('a room with tenancy history cannot be deleted', function () {
    $tenancy = Tenancy::factory()->ended()->create();

    expect(fn () => $tenancy->room->delete())->toViolateForeignKey();
});
