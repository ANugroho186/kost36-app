<?php

namespace App\Notifications;

use App\Enums\NotifyChannel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

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
     * Portal selalu. Ditambah saluran pilihan pengguna di Settings → Notifications, kalau
     * alamatnya ada. Email memakai kolom users.email, jadi tidak ada salinan kontak di tempat lain.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->notify_channel === NotifyChannel::Email && $notifiable->email !== null) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Email berisi kalimat yang sama dengan notifikasi portal dan satu tombol ke aplikasi,
     * tidak lebih. Isinya melewati penyedia email, jadi aturan "isi minimal" tetap berlaku.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(Str::limit($this->message(), 80))
            ->line($this->message())
            ->action(__('Open :app', ['app' => config('app.name')]), url($this->url()));
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
