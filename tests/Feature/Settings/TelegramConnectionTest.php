<?php

use App\Console\Commands\PollTelegram;
use App\Enums\NotifyChannel;
use App\Models\User;
use App\Support\Telegram\TelegramClient;
use App\Support\Telegram\TelegramException;
use App\Support\Telegram\TelegramLinks;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => config([
    'services.telegram.bot_token' => 'rahasia-token-bot',
    'services.telegram.bot_username' => 'kost36_test_bot',
]));

/**
 * Jawaban palsu Telegram: getUpdates berisi pesan-pesan ini, sendMessage selalu berhasil.
 *
 * @param  list<array<mixed>>  $updates
 */
function fakeTelegram(array $updates = []): void
{
    Http::fake([
        'api.telegram.org/*/getUpdates' => Http::response(['ok' => true, 'result' => $updates]),
        'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => []]),
    ]);
}

/**
 * @return array<mixed>
 */
function telegramMessage(int $updateId, string $text, int $chatId = 12345, string $chatType = 'private'): array
{
    return ['update_id' => $updateId, 'message' => ['chat' => ['id' => $chatId, 'type' => $chatType], 'text' => $text]];
}

function telegramReplies(): array
{
    return Http::recorded(fn (Request $request) => str_ends_with($request->url(), '/sendMessage'))
        ->map(fn (array $pair) => $pair[0]['text'])
        // Http::recorded mempertahankan nomor urut aslinya (urutan 0 adalah getUpdates).
        ->values()
        ->all();
}

// Menyambungkan dari Settings.

test('connecting opens a one-time link to the bot for this account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('notification-settings.telegram.connect'));

    $location = $response->headers->get('Location');
    expect($location)->toStartWith('https://t.me/kost36_test_bot?start=');

    $code = substr((string) $location, strlen('https://t.me/kost36_test_bot?start='));
    expect(strlen($code))->toBe(40)
        ->and(app(TelegramLinks::class)->consume($code)?->is($user))->toBeTrue()
        // Sekali pakai.
        ->and(app(TelegramLinks::class)->consume($code))->toBeNull();
});

test('a link expires after 15 minutes', function () {
    $code = app(TelegramLinks::class)->create(User::factory()->create());

    $this->travel(TelegramLinks::TTL_MINUTES + 1)->minutes();

    expect(app(TelegramLinks::class)->consume($code))->toBeNull();
});

test('connecting without a configured bot explains instead of failing', function () {
    config(['services.telegram.bot_token' => null]);

    $this->actingAs(User::factory()->create())
        ->post(route('notification-settings.telegram.connect'))
        ->assertRedirect(route('notification-settings.edit'))
        ->assertInertiaFlash('toast.type', 'error');
});

test('the settings page offers telegram only when the bot is configured', function (?string $token, bool $available) {
    config(['services.telegram.bot_token' => $token]);

    $this->actingAs(User::factory()->create())
        ->get(route('notification-settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('telegramAvailable', $available));
})->with([
    'bot diatur' => ['rahasia-token-bot', true],
    'bot belum diatur' => [null, false],
]);

test('disconnecting forgets the chat and falls back from telegram to the app', function (?NotifyChannel $before, ?NotifyChannel $after) {
    $user = User::factory()->create(['email' => 'budi@example.com', 'telegram_chat_id' => '12345', 'notify_channel' => $before]);

    $this->actingAs($user)
        ->delete(route('notification-settings.telegram.disconnect'))
        ->assertRedirect(route('notification-settings.edit'));

    expect($user->fresh())
        ->telegram_chat_id->toBeNull()
        ->notify_channel->toBe($after);
})->with([
    'memakai telegram' => [NotifyChannel::Telegram, null],
    'memakai email' => [NotifyChannel::Email, NotifyChannel::Email],
]);

test('guests cannot connect or disconnect', function (string $method, string $route) {
    $this->call($method, route($route))->assertRedirect(route('login'));
})->with([
    ['POST', 'notification-settings.telegram.connect'],
    ['DELETE', 'notification-settings.telegram.disconnect'],
]);

// Pesan yang masuk ke bot.

test('pressing Start with a valid link connects the account and chooses telegram', function () {
    $user = User::factory()->create();
    $code = app(TelegramLinks::class)->create($user);
    fakeTelegram([telegramMessage(100, "/start {$code}", chatId: 12345)]);

    $this->artisan('telegram:poll', ['--timeout' => 0])->assertSuccessful();

    expect($user->fresh())
        ->telegram_chat_id->toBe('12345')
        ->notify_channel->toBe(NotifyChannel::Telegram)
        ->and(telegramReplies())->toHaveCount(1)
        ->and(telegramReplies()[0])->toContain('Connected')
        // Pesan berikutnya dimulai dari update setelah ini, jadi tidak diproses dua kali.
        ->and(Cache::get(PollTelegram::OFFSET_KEY))->toBe(101);
});

test('the next poll continues after the last message', function () {
    Cache::forever(PollTelegram::OFFSET_KEY, 101);
    fakeTelegram();

    $this->artisan('telegram:poll', ['--timeout' => 0]);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/getUpdates') && $request['offset'] === 101);
});

test('a used, expired or unknown link connects nobody', function () {
    $user = User::factory()->create();
    $code = app(TelegramLinks::class)->create($user);
    fakeTelegram([
        telegramMessage(100, "/start {$code}", chatId: 111),
        // Kode yang sama dipakai lagi dari Telegram orang lain.
        telegramMessage(101, "/start {$code}", chatId: 222),
        telegramMessage(102, '/start kode-ngawur', chatId: 333),
    ]);

    $this->artisan('telegram:poll', ['--timeout' => 0]);

    expect($user->fresh()->telegram_chat_id)->toBe('111')
        ->and(User::query()->whereIn('telegram_chat_id', ['222', '333'])->exists())->toBeFalse()
        ->and(telegramReplies()[1])->toContain('expired')
        ->and(telegramReplies()[2])->toContain('expired');
});

test('group chats are ignored, so a group can never receive someone\'s notifications', function () {
    $user = User::factory()->create();
    $code = app(TelegramLinks::class)->create($user);
    fakeTelegram([telegramMessage(100, "/start {$code}", chatId: -987, chatType: 'group')]);

    $this->artisan('telegram:poll', ['--timeout' => 0]);

    expect($user->fresh()->telegram_chat_id)->toBeNull()
        ->and(telegramReplies())->toBe([]);
});

test('other messages get a short explanation', function () {
    fakeTelegram([telegramMessage(100, 'halo')]);

    $this->artisan('telegram:poll', ['--timeout' => 0]);

    expect(telegramReplies()[0])->toContain('only sends notifications');
});

test('without a bot token nothing is sent to telegram', function () {
    config(['services.telegram.bot_token' => null]);
    Http::fake();

    $this->artisan('telegram:poll', ['--timeout' => 0])->assertSuccessful();

    Http::assertNothingSent();
});

test('connection errors never reveal the bot token', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 28 for https://api.telegram.org/botrahasia-token-bot/getUpdates'));

    expect(fn () => app(TelegramClient::class)->getUpdates(0, 0))
        ->toThrow(fn (TelegramException $e) => expect($e->getMessage())->not->toContain('rahasia-token-bot'));
});

test('telegram is polled every minute, and a stuck run blocks it for two minutes at most', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'telegram:poll'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *')
        // Long polling selesai sebelum menit berikutnya.
        ->and((string) $event->command)->toContain('--timeout=45')
        // Proses latar di container Sail ikut mati sebelum melepas kunci, lalu kuncinya tertinggal.
        ->and($event->runInBackground)->toBeFalse()
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(2);
});
