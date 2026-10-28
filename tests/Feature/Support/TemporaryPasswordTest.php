<?php

use App\Models\Room;
use App\Models\User;
use App\Support\TemporaryPassword;
use Carbon\CarbonImmutable;

test('temporary passwords follow the format and their codes are random', function () {
    $room = Room::factory()->make(['number' => '3']);
    $user = User::factory()->make();
    $user->id = 9;

    $passwords = collect(range(1, 200))
        ->map(fn () => TemporaryPassword::generate(CarbonImmutable::parse('2026-07-15'), $room, $user));

    $passwords->each(fn (string $password) => expect($password)->toMatch('/^07-15-3-9-[A-HJKMNP-Z2-9]{6}-2026$/'));

    // 200 kode acak dari 31^6 kemungkinan hampir pasti berbeda semua.
    expect($passwords->unique()->count())->toBe(200);
});
