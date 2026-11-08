<?php

namespace App\Support\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Kode sekali pakai untuk menyambungkan akun ke Telegram: aplikasi membuka
 * https://t.me/<bot>?start=<kode>, lalu Telegram mengirim "/start <kode>" ke bot.
 *
 * Kode disimpan di cache selama 15 menit dalam bentuk hash. Siapa pun yang memegang kode bisa
 * menyambungkan Telegram-nya ke akun itu, jadi kodenya acak panjang, singkat umurnya, dan sekali pakai.
 */
class TelegramLinks
{
    public const TTL_MINUTES = 15;

    public function create(User $user): string
    {
        // Telegram hanya menerima huruf, angka, _ dan - (maks. 64) di parameter start.
        $code = Str::random(40);

        Cache::put($this->key($code), $user->id, now()->addMinutes(self::TTL_MINUTES));

        return $code;
    }

    public function consume(string $code): ?User
    {
        $userId = Cache::pull($this->key($code));

        return is_int($userId) ? User::find($userId) : null;
    }

    private function key(string $code): string
    {
        return 'telegram-link:'.hash('sha256', $code);
    }
}
