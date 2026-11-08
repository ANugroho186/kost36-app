<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Memberi tahu penghuni tentang pengumuman yang sudah tayang tapi belum diberitahukan, termasuk
 * pengumuman terjadwal yang tayang tanpa aksi siapa pun. Dipanggil command announcements:notify
 * setiap menit. Setiap pengumuman diberitahukan sekali saja; mengubahnya tidak mengirim ulang.
 */
class NotifyLiveAnnouncements
{
    /**
     * @return array{sent: int, failed: int}
     */
    public function handle(): array
    {
        $counts = ['sent' => 0, 'failed' => 0];

        Announcement::query()->live()->whereNull('notified_at')->each(function (Announcement $announcement) use (&$counts): void {
            try {
                if ($this->notify($announcement)) {
                    $counts['sent']++;
                }
            } catch (Throwable $e) {
                // notified_at ikut batal, jadi pengumuman ini dicoba lagi di menit berikutnya.
                report($e);
                $counts['failed']++;
            }
        });

        return $counts;
    }

    /**
     * Klaim lewat UPDATE ... WHERE notified_at IS NULL: kalau dua proses berjalan bersamaan,
     * hanya satu yang berhasil. Klaim dan notifikasi di antrean tersimpan dalam satu transaksi
     * (beforeCommit(), sama seperti pengingat jatuh tempo).
     */
    private function notify(Announcement $announcement): bool
    {
        return DB::transaction(function () use ($announcement): bool {
            $claimed = Announcement::query()
                ->whereKey($announcement->id)
                ->whereNull('notified_at')
                ->update(['notified_at' => now()]);

            if ($claimed === 0) {
                return false;
            }

            Notification::send(
                $this->tenantsOf($announcement->property_id),
                (new AnnouncementPublished($announcement->title))->beforeCommit(),
            );

            return true;
        });
    }

    /**
     * Penghuni yang sedang tinggal di kost itu, termasuk sewa nonaktif. Sama dengan yang boleh
     * membaca pengumumannya (AnnouncementPolicy::view).
     *
     * @return Collection<int, User>
     */
    private function tenantsOf(int $propertyId): Collection
    {
        return User::query()
            ->whereHas('currentOccupancy.tenancy.room', fn (Builder $query) => $query->where('property_id', $propertyId))
            ->get();
    }
}
