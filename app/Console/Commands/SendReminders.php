<?php

namespace App\Console\Commands;

use App\Actions\Reminders\SendDueReminders;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

/**
 * Pengingat jatuh tempo, masa tenggang, dan keputusan pemilik. Dijadwalkan harian pukul 08.00 WIB
 * di routes/console.php. Aman dijalankan berkali-kali: pengingat yang sudah tercatat dilewati.
 */
class SendReminders extends Command
{
    protected $signature = 'reminders:send
        {--date= : Hitung pengingat untuk tanggal ini (Y-m-d), bawaannya hari ini. Untuk mencoba di lokal.}';

    protected $description = 'Send due-date, grace-period, and owner decision reminders for today';

    public function handle(SendDueReminders $send): int
    {
        $option = $this->option('date');

        try {
            $day = is_string($option) ? CarbonImmutable::createFromFormat('!Y-m-d', $option) : CarbonImmutable::today();
        } catch (Throwable) {
            $day = null;
        }

        // createFromFormat menggeser tanggal yang tidak ada (2026-13-01 menjadi 2027-01-01) tanpa error,
        // jadi hasilnya harus ditulis persis sama dengan isian.
        if (! $day instanceof CarbonImmutable || (is_string($option) && $day->format('Y-m-d') !== $option)) {
            $this->error('Use the date format Y-m-d, for example 2026-11-08.');

            return self::INVALID;
        }

        $counts = $send->handle($day);

        $this->info("Reminders for {$day->toDateString()}: {$counts['sent']} sent, {$counts['alreadySent']} already sent, {$counts['failed']} failed.");

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
