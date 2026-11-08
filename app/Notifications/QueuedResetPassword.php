<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;

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
}
