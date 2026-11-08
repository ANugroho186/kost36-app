<?php

namespace App\Console\Commands;

use App\Actions\Announcements\NotifyLiveAnnouncements;
use Illuminate\Console\Command;

/**
 * Dijadwalkan setiap menit di routes/console.php, supaya pengumuman terjadwal diberitahukan
 * paling lambat semenit setelah jam tayangnya.
 */
class NotifyAnnouncements extends Command
{
    protected $signature = 'announcements:notify';

    protected $description = 'Notify tenants about announcements that have just gone live';

    public function handle(NotifyLiveAnnouncements $notify): int
    {
        $counts = $notify->handle();

        $this->info("Announcements: {$counts['sent']} notified, {$counts['failed']} failed.");

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
