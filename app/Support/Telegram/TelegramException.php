<?php

namespace App\Support\Telegram;

use RuntimeException;

/**
 * Kegagalan memanggil API Telegram. Pesannya sengaja tidak memuat URL permintaan, karena URL itu
 * berisi token bot. `status` berisi kode HTTP dari Telegram, atau 0 kalau server tidak terjangkau.
 */
class TelegramException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 0)
    {
        parent::__construct($message, $status);
    }
}
