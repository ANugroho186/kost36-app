<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jam mengikuti zona waktu aplikasi (Asia/Jakarta). Di lokal jalankan `sail artisan schedule:work`,
// di server butuh cron yang memanggil `php artisan schedule:run` setiap menit.
Schedule::command('reminders:send')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('announcements:notify')->everyMinute()->withoutOverlapping();
