<?php

use App\Enums\NotifyChannel;
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

test('usernames are stored in lowercase', function () {
    $user = User::factory()->create(['username' => 'Budi']);

    expect($user->fresh()->username)->toBe('budi');
});

test('emails are stored in lowercase', function () {
    $user = User::factory()->create(['email' => 'Budi@Example.com']);

    expect($user->fresh()->email)->toBe('budi@example.com');
});

test('usernames that differ only in case are duplicates', function () {
    User::factory()->create(['username' => 'budi']);

    User::factory()->create(['username' => 'Budi']);
})->throws(UniqueConstraintViolationException::class);

test('notification settings cannot be mass assigned and the telegram chat stays hidden', function () {
    $user = User::factory()->create([
        'notify_channel' => NotifyChannel::Email,
        'telegram_chat_id' => '123456789',
    ]);
    $user->update(['notify_channel' => NotifyChannel::Telegram->value, 'telegram_chat_id' => '999']);

    // Factory boleh mengisi semua kolom; update() dari input biasa tidak.
    expect($user->fresh()->notify_channel)->toBe(NotifyChannel::Email)
        ->and($user->fresh()->telegram_chat_id)->toBe('123456789')
        ->and($user->toArray())->not->toHaveKey('telegram_chat_id');
});
