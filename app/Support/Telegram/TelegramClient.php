<?php

namespace App\Support\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Pemanggil Bot API Telegram yang dipakai aplikasi ini: mengambil pesan masuk (getUpdates, long
 * polling) dan mengirim pesan (sendMessage). Tanpa webhook, supaya bisa jalan di laptop tanpa URL publik.
 */
class TelegramClient
{
    public function isConfigured(): bool
    {
        return $this->token() !== null && $this->botUsername() !== null;
    }

    public function botUsername(): ?string
    {
        $username = config('services.telegram.bot_username');

        return is_string($username) && $username !== '' ? ltrim($username, '@') : null;
    }

    /**
     * Pesan masuk sejak $offset. Telegram menahan jawaban sampai ada pesan atau $timeout detik
     * lewat, jadi pesan diproses hampir seketika tanpa memanggil API terus-menerus.
     *
     * @return list<array<mixed>>
     */
    public function getUpdates(int $offset, int $timeout): array
    {
        $result = $this->call('getUpdates', [
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => ['message'],
        ], $timeout + 10)->json('result');

        return is_array($result) ? array_values(array_filter($result, is_array(...))) : [];
    }

    public function sendMessage(string $chatId, string $text): void
    {
        $this->call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'link_preview_options' => ['is_disabled' => true],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws TelegramException
     */
    private function call(string $method, array $payload, int $timeout = 10): Response
    {
        $token = $this->token() ?? throw new TelegramException('TELEGRAM_BOT_TOKEN is not set.');

        try {
            return Http::baseUrl("https://api.telegram.org/bot{$token}/")
                ->acceptJson()
                ->timeout($timeout)
                ->post($method, $payload)
                ->throw();
        } catch (ConnectionException) {
            // Pesan asli dari cURL memuat URL lengkap, termasuk token bot. Jangan sampai masuk log.
            throw new TelegramException("Telegram could not be reached ({$method}).");
        } catch (RequestException $e) {
            $description = $e->response->json('description');

            throw new TelegramException(
                "Telegram refused {$method}: ".(is_string($description) ? $description : 'unknown error'),
                $e->response->status(),
            );
        }
    }

    private function token(): ?string
    {
        $token = config('services.telegram.bot_token');

        return is_string($token) && $token !== '' ? $token : null;
    }
}
