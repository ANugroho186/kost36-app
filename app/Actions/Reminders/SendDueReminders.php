<?php

namespace App\Actions\Reminders;

use App\Enums\ReminderType;
use App\Models\Tenancy;
use App\Models\User;
use App\Notifications\RentReminder;
use App\Support\ReminderSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Mengirim pengingat yang jatuh pada satu hari untuk semua sewa yang menempati kamar.
 * Dipanggil harian oleh command reminders:send (scheduler 08.00 WIB).
 */
class SendDueReminders
{
    /**
     * @return array{sent: int, alreadySent: int, failed: int}
     */
    public function handle(CarbonImmutable $day): array
    {
        $counts = ['sent' => 0, 'alreadySent' => 0, 'failed' => 0];

        Tenancy::query()->occupying()->with('room.property')->each(function (Tenancy $tenancy) use ($day, &$counts): void {
            $due = ReminderSchedule::dueOn($tenancy, $day);

            if ($due === null) {
                return;
            }

            try {
                $this->send($tenancy, $due['type'], $due['dueDate']);
                $counts['sent']++;
            } catch (UniqueConstraintViolationException) {
                // Sudah tercatat di reminder_logs, misalnya command berjalan dua kali hari ini.
                $counts['alreadySent']++;
            } catch (Throwable $e) {
                // Satu sewa yang gagal tidak boleh menghentikan pengingat sewa lain.
                report($e);
                $counts['failed']++;
            }
        });

        return $counts;
    }

    /**
     * Catatan di reminder_logs dan notifikasi di antrean tersimpan dalam satu transaksi. Antrean
     * `database` memakai koneksi yang sama, jadi keduanya tersimpan bersama atau batal bersama.
     * Karena itu notifikasinya memakai beforeCommit(): config queue `after_commit` bernilai true,
     * yang akan menundanya sampai transaksi selesai.
     */
    private function send(Tenancy $tenancy, ReminderType $type, CarbonImmutable $dueDate): void
    {
        DB::transaction(function () use ($tenancy, $type, $dueDate): void {
            // Unique (tenancy_id, due_date, type): baris kedua untuk pengingat yang sama ditolak database.
            $tenancy->reminderLogs()->create([
                'due_date' => $dueDate,
                'type' => $type,
                'sent_at' => now(),
            ]);

            Notification::send(
                $this->recipients($tenancy, $type),
                (new RentReminder($type, $tenancy->room->number, $dueDate->toDateString(), $tenancy->id))->beforeCommit(),
            );
        });
    }

    /**
     * Pemilik kost, atau penghuni sewa itu yang belum keluar dan punya akun. Penghuni tanpa akun
     * tidak menerima notifikasi portal. Pengingatnya tetap tercatat, jadi tidak dicoba lagi.
     *
     * @return Collection<int, User>
     */
    private function recipients(Tenancy $tenancy, ReminderType $type): Collection
    {
        if ($type->isForOwner()) {
            return User::query()->whereKey($tenancy->room->property->owner_id)->get();
        }

        return User::query()
            ->whereHas('occupancies', fn (Builder $query) => $query
                ->where('tenancy_id', $tenancy->id)
                ->whereNull('moved_out_at'))
            ->get();
    }
}
