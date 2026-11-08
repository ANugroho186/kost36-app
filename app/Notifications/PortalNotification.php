<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Dasar semua notifikasi aplikasi: satu kalimat dan satu tautan ke halaman yang bersangkutan.
 *
 * - Dikirim lewat queue, jadi request pengguna tidak menunggu pengiriman.
 * - Isinya sengaja minimal. Notifikasi tersimpan di tabel `notifications` dan bisa lebih lama
 *   daripada datanya (misalnya permintaan survei dihapus 30 hari setelah selesai), jadi jangan
 *   menyimpan nama, kontak, atau data yang tidak boleh dilihat penerimanya.
 * - Data yang dibutuhkan dikirim lewat constructor sebagai nilai biasa (bukan model), supaya
 *   notifikasi tetap terkirim walaupun datanya sudah dihapus sebelum queue memprosesnya.
 */
abstract class PortalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Saluran email dan Telegram ditambahkan di branch feat/notifikasi-telegram-email.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Isi kolom `data` di tabel notifications.
     *
     * @return array{message: string, url: string}
     */
    final public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message(),
            'url' => $this->url(),
        ];
    }

    abstract protected function message(): string;

    /**
     * Path relatif di aplikasi ini (misalnya "/owner/rental-inquiries"), bukan URL lengkap,
     * supaya tautan notifikasi tidak bisa mengarah ke situs lain.
     */
    abstract protected function url(): string;
}
