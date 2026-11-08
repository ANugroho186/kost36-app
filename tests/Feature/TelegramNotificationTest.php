<?php

use App\Enums\NotifyChannel;
use App\Models\Parcel;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\ParcelHeldByOwner;
use App\Notifications\PortalNotification;
use App\Support\Telegram\TelegramException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => config([
    'services.telegram.bot_token' => 'rahasia-token-bot',
    'services.telegram.bot_username' => 'kost36_test_bot',
]));

function telegramUser(array $attributes = []): User
{
    return User::factory()->create([
        'notify_channel' => NotifyChannel::Telegram,
        'telegram_chat_id' => '12345',
        ...$attributes,
    ]);
}

test('telegram is used only for people who chose it, are connected, and when the bot is set up', function (array $attributes, bool $configured, bool $expected) {
    if (! $configured) {
        config(['services.telegram.bot_token' => null]);
    }

    $channels = (new ParcelHeldByOwner('JNE123'))->via(telegramUser($attributes));

    expect(in_array(TelegramChannel::class, $channels, true))->toBe($expected)
        ->and($channels)->toContain('database');
})->with([
    'memilih dan tersambung' => [[], true, true],
    'memilih email' => [['notify_channel' => NotifyChannel::Email, 'email' => 'budi@example.com'], true, false],
    'belum tersambung' => [['telegram_chat_id' => null], true, false],
    'bot belum diatur di .env' => [[], false, false],
]);

test('the telegram message is the same sentence with a link to the app', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
    $user = telegramUser();

    $user->notifyNow(new ParcelHeldByOwner('JNE123'));

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendMessage')
        && $request['chat_id'] === '12345'
        && $request['text'] === "Parcel JNE123 is at the owner's place. You can pick it up.\n\n".url('/parcels')
        && $request['link_preview_options'] === ['is_disabled' => true]);
});

test('a real event reaches someone on telegram, never with what is inside the parcel', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
    $scenario = accessScenario();
    $scenario['occupant']->forceFill(['notify_channel' => NotifyChannel::Telegram, 'telegram_chat_id' => '12345'])->save();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create(['tracking_number' => 'JNE123', 'item_name' => 'Obat pribadi']);

    $this->actingAs($scenario['owner'])->put(route('owner.parcels.held', $parcel));

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendMessage')
        && str_contains((string) $request['text'], 'JNE123')
        && ! str_contains((string) $request['text'], 'Obat pribadi'));
});

test('someone who blocked the bot is disconnected instead of retried forever', function (NotifyChannel $channel, ?NotifyChannel $after) {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden: bot was blocked by the user'], 403)]);
    $user = telegramUser(['notify_channel' => $channel, 'email' => 'budi@example.com']);

    // Tidak melempar error: antrean tidak perlu mencoba ulang.
    app(TelegramChannel::class)->send($user, new ParcelHeldByOwner('JNE123'));

    expect($user->fresh())
        ->telegram_chat_id->toBeNull()
        ->notify_channel->toBe($after);
})->with([
    'memakai telegram' => [NotifyChannel::Telegram, null],
    // Kasus jarang: masih tersambung tapi sudah pindah ke email. Pilihan email tidak diubah.
    'sudah pindah ke email' => [NotifyChannel::Email, NotifyChannel::Email],
]);

test('a temporary telegram error is raised so the queue tries again', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Too Many Requests'], 429)]);
    $user = telegramUser();

    expect(fn () => app(TelegramChannel::class)->send($user, new ParcelHeldByOwner('JNE123')))
        ->toThrow(TelegramException::class);

    expect($user->fresh()->telegram_chat_id)->toBe('12345');
});

test('nothing is sent once someone has disconnected', function () {
    Http::fake();
    $user = telegramUser(['telegram_chat_id' => null]);

    app(TelegramChannel::class)->send($user, new ParcelHeldByOwner('JNE123'));

    Http::assertNothingSent();
});

test('notifications are retried a few times with a pause', function () {
    $notification = new ParcelHeldByOwner('JNE123');

    expect($notification)->toBeInstanceOf(PortalNotification::class)
        ->and($notification->tries)->toBe(3)
        ->and($notification->backoff())->toBe([10, 60]);
});
