<?php

namespace App\Notifications\Channels;

use App\Enums\NotifyChannel;
use App\Models\User;
use App\Notifications\PortalNotification;
use App\Support\Telegram\TelegramClient;
use App\Support\Telegram\TelegramException;
use Illuminate\Notifications\Notification;

/**
 * Mengirim notifikasi ke chat Telegram pengguna lewat bot. Dipilih di PortalNotification::via().
 */
class TelegramChannel
{
    public function __construct(private TelegramClient $telegram) {}

    public function send(object $notifiable, Notification $notification): void
    {
        // Job di antrean memuat ulang akun saat dijalankan: kalau Telegram sudah diputus, lewati.
        if (! $notifiable instanceof User || $notifiable->telegram_chat_id === null || ! $notification instanceof PortalNotification) {
            return;
        }

        try {
            $this->telegram->sendMessage($notifiable->telegram_chat_id, $notification->toTelegram($notifiable));
        } catch (TelegramException $e) {
            // 403: pengguna memblokir bot. Mencoba ulang tidak akan berhasil, jadi sambungannya diputus
            // dan pilihan kembali ke portal saja. Kesalahan lain dilempar lagi supaya antrean mencoba ulang.
            if ($e->status !== 403) {
                throw $e;
            }

            $notifiable->forceFill([
                'telegram_chat_id' => null,
                'notify_channel' => $notifiable->notify_channel === NotifyChannel::Telegram ? null : $notifiable->notify_channel,
            ])->save();
        }
    }
}
