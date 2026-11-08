<?php

namespace App\Http\Controllers\Settings;

use App\Enums\NotifyChannel;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Telegram\TelegramClient;
use App\Support\Telegram\TelegramLinks;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyambungkan dan memutus Telegram untuk akun yang login. chat_id sendiri diisi oleh
 * telegram:poll setelah pengguna menekan Start di bot (lihat HandleTelegramUpdate).
 */
class TelegramConnectionController extends Controller
{
    /**
     * Membuat kode sekali pakai, lalu membuka https://t.me/<bot>?start=<kode> di browser/HP pengguna.
     */
    public function store(#[CurrentUser] User $user, TelegramClient $telegram, TelegramLinks $links): Response
    {
        $username = $telegram->botUsername();

        if (! $telegram->isConfigured() || $username === null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Telegram is not set up yet.')]);

            return to_route('notification-settings.edit');
        }

        // Halaman luar aplikasi: Inertia::location memaksa browser pindah penuh, bukan kunjungan Inertia.
        return Inertia::location("https://t.me/{$username}?start={$links->create($user)}");
    }

    public function destroy(#[CurrentUser] User $user): RedirectResponse
    {
        $user->forceFill([
            'telegram_chat_id' => null,
            // Tanpa Telegram, pilihan Telegram kembali ke portal saja. Pilihan email tetap.
            'notify_channel' => $user->notify_channel === NotifyChannel::Telegram ? null : $user->notify_channel,
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Telegram disconnected.')]);

        return to_route('notification-settings.edit');
    }
}
