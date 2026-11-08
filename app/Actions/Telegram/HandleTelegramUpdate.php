<?php

namespace App\Actions\Telegram;

use App\Enums\NotifyChannel;
use App\Support\Telegram\TelegramClient;
use App\Support\Telegram\TelegramLinks;

/**
 * Satu pesan masuk ke bot. Bot ini hanya mengirim notifikasi, jadi satu-satunya perintah yang
 * dipahami adalah "/start <kode>" untuk menyambungkan akun. Hanya chat pribadi yang dilayani:
 * chat grup tidak boleh tersambung ke akun siapa pun.
 */
class HandleTelegramUpdate
{
    public function __construct(private TelegramClient $telegram, private TelegramLinks $links) {}

    /**
     * @param  array<mixed>  $update
     */
    public function handle(array $update): void
    {
        $message = $update['message'] ?? null;
        $chat = is_array($message) ? ($message['chat'] ?? null) : null;
        $text = is_array($message) ? ($message['text'] ?? null) : null;

        if (! is_array($chat) || ($chat['type'] ?? null) !== 'private' || ! is_int($chat['id'] ?? null) || ! is_string($text)) {
            return;
        }

        $chatId = (string) $chat['id'];

        if (preg_match('/^\/start(?:\s+([A-Za-z0-9_-]{1,64}))?$/', trim($text), $matches) !== 1) {
            $this->telegram->sendMessage($chatId, __('This bot only sends notifications from :app. Manage them in Settings → Notifications.', [
                'app' => config('app.name'),
            ]));

            return;
        }

        $user = isset($matches[1]) ? $this->links->consume($matches[1]) : null;

        if ($user === null) {
            $this->telegram->sendMessage($chatId, __('This link has expired or was already used. Open :app, go to Settings → Notifications, and press Connect Telegram again.', [
                'app' => config('app.name'),
            ]));

            return;
        }

        // Menyambungkan Telegram berarti ingin menerima notifikasi di sana, jadi saluran ikut dipilih.
        $user->forceFill([
            'telegram_chat_id' => $chatId,
            'notify_channel' => NotifyChannel::Telegram,
        ])->save();

        $this->telegram->sendMessage($chatId, __('Connected. You will receive :app notifications here.', [
            'app' => config('app.name'),
        ]));
    }
}
