<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jam mengikuti zona waktu aplikasi (Asia/Jakarta). Di lokal jalankan `sail artisan schedule:work`,
// di server butuh cron yang memanggil `php artisan schedule:run` setiap menit.
//
// withoutOverlapping(n): kunci anti-tumpang-tindih kedaluwarsa setelah n menit. Bawaan Laravel 24 jam,
// jadi satu proses yang mati di tengah jalan (misalnya container dimatikan) akan memblokir command
// itu seharian. Pernah terjadi pada telegram:poll (2026-11-08).
Schedule::command('reminders:send')->dailyAt('08:00')->withoutOverlapping(60);
Schedule::command('announcements:notify')->everyMinute()->withoutOverlapping(5);
// Long polling: menunggu pesan baru sampai 45 detik, lalu selesai sebelum menit berikutnya. Tidak
// pakai runInBackground: proses latar itu ikut mati di container Sail sebelum sempat melepas kuncinya.
Schedule::command('telegram:poll --timeout=45')->everyMinute()->withoutOverlapping(2);
