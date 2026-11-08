<?php

use App\Enums\NotifyChannel;
use App\Models\Parcel;
use App\Models\User;
use App\Notifications\ParcelHeldByOwner;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

// Halaman pengaturan.

test('the page shows the current choice without the telegram chat itself', function () {
    $user = User::factory()->create(['email' => 'budi@example.com', 'notify_channel' => NotifyChannel::Email, 'telegram_chat_id' => '987654321']);

    $this->actingAs($user)
        ->get(route('notification-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/notifications')
            ->where('channel', 'email')
            ->where('email', 'budi@example.com')
            ->where('telegramConnected', true),
        )
        ->assertDontSee('987654321');
});

test('people choose email or the app only', function (?string $input, ?NotifyChannel $expected) {
    $user = User::factory()->create(['email' => 'budi@example.com', 'notify_channel' => NotifyChannel::Email]);

    $this->actingAs($user)
        ->put(route('notification-settings.update'), ['notify_channel' => $input])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('notification-settings.edit'));

    expect($user->fresh()->notify_channel)->toBe($expected);
})->with([
    'email' => ['email', NotifyChannel::Email],
    // Pilihan "di aplikasi saja" dikirim sebagai string kosong, lalu diubah menjadi null oleh Laravel.
    'di aplikasi saja' => ['', null],
]);

test('a channel needs somewhere to send to', function (array $user, string $channel, string $message) {
    $user = User::factory()->create($user);

    $this->actingAs($user)
        ->put(route('notification-settings.update'), ['notify_channel' => $channel])
        ->assertInvalid(['notify_channel' => $message]);

    expect($user->fresh()->notify_channel)->toBeNull();
})->with([
    'email tanpa alamat email' => [['email' => null], 'email', 'Add an email address'],
    'telegram belum tersambung' => [['email' => 'budi@example.com'], 'telegram', 'Connect Telegram first'],
    'saluran tidak dikenal' => [['email' => 'budi@example.com'], 'whatsapp', 'invalid'],
]);

test('the telegram chat cannot be set from the form', function () {
    $user = User::factory()->create(['email' => 'budi@example.com']);

    $this->actingAs($user)->put(route('notification-settings.update'), [
        'notify_channel' => 'email',
        'telegram_chat_id' => '123456789',
    ]);

    expect($user->fresh()->telegram_chat_id)->toBeNull();
});

test('removing the email address switches back to the app only', function () {
    $user = User::factory()->create(['email' => 'budi@example.com', 'notify_channel' => NotifyChannel::Email]);

    $this->actingAs($user)
        ->patch(route('profile.update'), ['name' => $user->name, 'email' => ''])
        ->assertSessionHasNoErrors();

    expect($user->fresh())
        ->email->toBeNull()
        ->notify_channel->toBeNull();
});

test('changing the email address keeps email notifications', function () {
    $user = User::factory()->create(['email' => 'budi@example.com', 'notify_channel' => NotifyChannel::Email]);

    $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'email' => 'budi.baru@example.com']);

    expect($user->fresh()->notify_channel)->toBe(NotifyChannel::Email);
});

test('guests are sent to the login page', function () {
    $this->get(route('notification-settings.edit'))->assertRedirect(route('login'));
});

// Pengiriman.

test('notifications go by email only to people who chose it and have an address', function (array $attributes, array $expected) {
    $user = User::factory()->create($attributes);

    expect((new ParcelHeldByOwner('JNE123'))->via($user))->toBe($expected);
})->with([
    'memilih email' => [['email' => 'budi@example.com', 'notify_channel' => NotifyChannel::Email], ['database', 'mail']],
    'di aplikasi saja' => [['email' => 'budi@example.com', 'notify_channel' => null], ['database']],
    'memilih email tapi alamatnya kosong' => [['email' => null, 'notify_channel' => NotifyChannel::Email], ['database']],
]);

test('the email says the same short sentence with a button back to the app', function () {
    $user = User::factory()->create(['email' => 'budi@example.com', 'notify_channel' => NotifyChannel::Email]);

    $mail = (new ParcelHeldByOwner('JNE123'))->toMail($user);

    expect($mail->subject)->toBe("Parcel JNE123 is at the owner's place. You can pick it up.")
        ->and($mail->introLines)->toBe(["Parcel JNE123 is at the owner's place. You can pick it up."])
        ->and($mail->actionUrl)->toBe(url('/parcels'));
});

test('a real event reaches someone who chose email through both channels', function () {
    Notification::fake();
    $scenario = accessScenario();
    $scenario['occupant']->forceFill(['email' => 'budi@example.com', 'notify_channel' => NotifyChannel::Email])->save();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create();

    $this->actingAs($scenario['owner'])->put(route('owner.parcels.held', $parcel));

    Notification::assertSentTo(
        $scenario['occupant'],
        ParcelHeldByOwner::class,
        fn (ParcelHeldByOwner $notification, array $channels) => $channels === ['database', 'mail'],
    );
});
