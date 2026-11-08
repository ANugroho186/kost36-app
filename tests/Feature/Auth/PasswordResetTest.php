<?php

use App\Http\Responses\PasswordResetLinkRequestResponse;
use App\Models\User;
use App\Notifications\QueuedResetPassword;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, QueuedResetPassword::class);
});

test('the reset email is queued, so registered emails are not answered more slowly', function () {
    // Form ini tidak menunggu server email. Sisa waktu (bcrypt token) ditutup timebox
    // auth.timebox_duration; diukur di lokal 2026-11-08: semua jalur sekitar 1000 ms.
    expect(new QueuedResetPassword('token'))->toBeInstanceOf(ShouldQueue::class)
        ->toBeInstanceOf(ShouldBeEncrypted::class);
});

test('the reset token waits in the queue encrypted, not readable from the database', function () {
    config(['queue.default' => 'database']);
    $user = User::factory()->create(['email' => 'budi@example.com']);

    $this->post(route('password.email'), ['email' => 'budi@example.com']);

    $command = json_decode((string) DB::table('jobs')->value('payload'), true)['data']['command'];

    // Job biasa disimpan sebagai objek PHP terbaca ("O:..."). Yang terenkripsi hanya bisa dibuka dengan APP_KEY.
    expect($command)->not->toStartWith('O:')
        ->and(Crypt::decryptString($command))->toContain(QueuedResetPassword::class);
});

test('reset link requests do not reveal whether an email is registered', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'budi@example.com']);

    $this->post(route('password.email'), ['email' => 'budi@example.com'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', PasswordResetLinkRequestResponse::MESSAGE);

    $this->post(route('password.email'), ['email' => 'tidak-terdaftar@example.com'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', PasswordResetLinkRequestResponse::MESSAGE);

    Notification::assertSentTo($user, QueuedResetPassword::class);
    Notification::assertSentTimes(QueuedResetPassword::class, 1);
});

test('throttled reset link requests look the same as successful ones', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', PasswordResetLinkRequestResponse::MESSAGE);

    Notification::assertSentTimes(QueuedResetPassword::class, 1);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, QueuedResetPassword::class, function ($notification) {
        $response = $this->get(route('password.reset', $notification->token));

        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, QueuedResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'))
            // Halaman login mengingatkan bahwa login memakai username, bukan email.
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'Log in with your username'));

        return true;
    });
});

test('the reset email tells people their username, because they log in with it', function () {
    Notification::fake();
    $user = User::factory()->create(['username' => 'penghuni9', 'email' => 'budi@example.com']);

    $this->post(route('password.email'), ['email' => 'budi@example.com']);

    Notification::assertSentTo($user, QueuedResetPassword::class, function (QueuedResetPassword $notification) use ($user) {
        $mail = $notification->toMail($user);

        // Tautan reset bawaan tetap ada, dan username ditambahkan di bagian awal email.
        return $mail->actionUrl !== null
            && in_array('Your username is penghuni9. Log in with it, not with your email address.', $mail->introLines, true);
    });
});

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('email');
});
