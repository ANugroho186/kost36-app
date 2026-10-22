<?php

use App\Enums\TenancyStatus;
use App\Enums\UserRole;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;

test('the default seeder creates no accounts outside local', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(0);
});

test('the demo seeder builds the four room scenarios', function () {
    $this->seed(DemoSeeder::class);

    $rooms = Room::with('tenancies.activeOccupants')->orderBy('number')->get();
    $occupying = fn (Room $room): ?Tenancy => $room->tenancies
        ->first(fn (Tenancy $tenancy) => $tenancy->status !== TenancyStatus::Ended);

    expect(User::firstWhere('username', 'pemilik')->role)->toBe(UserRole::Owner)
        ->and($rooms)->toHaveCount(4)
        ->and($occupying($rooms[0])->activeOccupants)->toHaveCount(2)
        ->and($occupying($rooms[1])->paid_until->isPast())->toBeTrue()
        ->and($occupying($rooms[2])->status)->toBe(TenancyStatus::Inactive)
        ->and($occupying($rooms[3]))->toBeNull();
});
