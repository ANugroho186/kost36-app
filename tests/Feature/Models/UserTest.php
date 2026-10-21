<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('new users are tenants by default', function () {
    $user = User::create([
        'name' => 'Penghuni Contoh',
        'username' => 'penghuni',
        'password' => 'password',
    ]);

    expect($user->fresh()->role)->toBe(UserRole::Tenant);
});

test('role cannot be mass assigned', function () {
    $user = new User(['role' => UserRole::Owner->value]);

    expect($user->role)->toBeNull();
});

test('email is optional and multiple users may have no email', function () {
    User::factory()->create(['email' => null]);
    User::factory()->create(['email' => null]);

    expect(User::whereNull('email')->count())->toBe(2);
});

test('username must be unique', function () {
    User::factory()->create(['username' => 'penghuni']);

    User::factory()->create(['username' => 'penghuni']);
})->throws(UniqueConstraintViolationException::class);
