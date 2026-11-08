<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email reset password bawaan Laravel, tapi lewat antrean. Ada dua alasan:
 *
 * - Form lupa password tidak menunggu server email. Jalur "email terdaftar" menjadi cukup cepat
 *   dan stabil untuk ditutup timebox (config auth.timebox_duration), sehingga lamanya jawaban
 *   tidak membocorkan email mana yang terdaftar.
 * - ShouldBeEncrypted: job di tabel jobs (dan failed_jobs kalau gagal) berisi token reset asli.
 *   Isinya dienkripsi dengan APP_KEY, jadi pembaca database tidak bisa memakai token itu.
 */
class QueuedResetPassword extends ResetPassword implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    /**
     * Email bawaan, ditambah username akunnya. Login memakai username, sedangkan penghuni baru
     * saja memakai email untuk reset, jadi mudah tertukar. Penerima email ini memang pemilik akunnya.
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        // Langsung lewat pembangun email bawaan. parent::toMail() bisa mengembalikan Mailable kalau
        // ResetPassword::toMailUsing() dipakai, padahal proyek ini tidak memakainya.
        $mail = $this->buildMailMessage($this->resetUrl($notifiable));

        if ($notifiable instanceof User) {
            $mail->introLines[] = __('Your username is :username. Log in with it, not with your email address.', [
                'username' => $notifiable->username,
            ]);
        }

        return $mail;
    }
}
