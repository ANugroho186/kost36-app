<?php

namespace App\Http\Requests\Settings;

use App\Enums\NotifyChannel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Saluran notifikasi milik user yang login. Hanya mengubah akunnya sendiri, jadi tidak ada ID di URL.
 */
class NotificationSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Kosong = portal saja.
            'notify_channel' => ['nullable', Rule::enum(NotifyChannel::class)],
        ];
    }

    /**
     * Saluran hanya bisa dipilih kalau alamatnya sudah ada.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();
                $channel = $this->enum('notify_channel', NotifyChannel::class);

                if ($channel === NotifyChannel::Email && $user?->email === null) {
                    $validator->errors()->add('notify_channel', __('Add an email address to your profile first.'));
                }

                if ($channel === NotifyChannel::Telegram && $user?->telegram_chat_id === null) {
                    $validator->errors()->add('notify_channel', __('Connect Telegram first.'));
                }
            },
        ];
    }
}
