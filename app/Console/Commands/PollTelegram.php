<?php

namespace App\Console\Commands;

use App\Actions\Telegram\HandleTelegramUpdate;
use App\Support\Telegram\TelegramClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Mengambil pesan baru untuk bot Telegram (long polling) dan memprosesnya. Dijadwalkan setiap
 * menit di routes/console.php. Tidak berbuat apa-apa kalau token bot belum diisi di .env.
 */
class PollTelegram extends Command
{
    /**
     * Posisi pesan terakhir yang sudah diproses. Telegram menganggap pesan sebelum offset sudah
     * diterima, jadi pesan yang sama tidak diproses dua kali.
     */
    public const OFFSET_KEY = 'telegram:update-offset';

    protected $signature = 'telegram:poll {--timeout=50 : Detik menunggu pesan baru dari Telegram}';

    protected $description = 'Fetch and handle new messages sent to the Telegram bot';

    public function handle(TelegramClient $telegram, HandleTelegramUpdate $handler): int
    {
        if (! $telegram->isConfigured()) {
            $this->info('Telegram is not configured (TELEGRAM_BOT_TOKEN, TELEGRAM_BOT_USERNAME).');

            return self::SUCCESS;
        }

        $offset = (int) Cache::get(self::OFFSET_KEY, 0);
        $updates = $telegram->getUpdates($offset, (int) $this->option('timeout'));

        foreach ($updates as $update) {
            $updateId = $update['update_id'] ?? null;

            if (! is_int($updateId)) {
                continue;
            }

            // Disimpan sebelum diproses: pesan yang membuat error tidak diulang terus setiap menit.
            Cache::forever(self::OFFSET_KEY, max($offset, $updateId + 1));

            try {
                $handler->handle($update);
            } catch (Throwable $e) {
                report($e);
            }
        }

        $this->info('Telegram: '.count($updates).' new message(s).');

        return self::SUCCESS;
    }
}
