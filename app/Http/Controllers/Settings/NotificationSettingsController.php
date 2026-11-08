<?php

namespace App\Http\Controllers\Settings;

use App\Enums\NotifyChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\NotificationSettingsRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Settings → Notifications: saluran selain portal untuk pemilik maupun penghuni.
 */
class NotificationSettingsController extends Controller
{
    public function edit(#[CurrentUser] User $user): Response
    {
        return Inertia::render('settings/notifications', [
            'channel' => $user->notify_channel?->value,
            // Alamat email milik user sendiri. Chat Telegram tidak dikirim, cukup status tersambung.
            'email' => $user->email,
            'telegramConnected' => $user->telegram_chat_id !== null,
        ]);
    }

    public function update(NotificationSettingsRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $user->forceFill([
            'notify_channel' => $request->enum('notify_channel', NotifyChannel::class),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification settings saved.')]);

        return to_route('notification-settings.edit');
    }
}
