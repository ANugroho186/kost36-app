<?php

namespace App\Actions\Complaints;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintResolved;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Pemilik memindahkan keluhan: dilaporkan → diproses → selesai, atau membukanya lagi.
 */
class ChangeComplaintStatus
{
    /**
     * Catatan perbaikan ($repair) masuk ke maintenance_logs, hanya saat keluhan diselesaikan.
     *
     * @param  array{performed_at: CarbonImmutable, description: string}|null  $repair
     */
    public function handle(Complaint $complaint, ComplaintStatus $status, ?array $repair = null): void
    {
        $wasResolved = $complaint->status === ComplaintStatus::Resolved;

        DB::transaction(function () use ($complaint, $status, $repair): void {
            $complaint->forceFill([
                'status' => $status,
                // Waktu selesai tetap yang pertama kalau keluhan yang sudah selesai disimpan ulang,
                // dan dikosongkan kalau keluhan dibuka lagi.
                'resolved_at' => $status === ComplaintStatus::Resolved
                    ? ($complaint->resolved_at ?? now())
                    : null,
            ])->save();

            if ($status === ComplaintStatus::Resolved && $repair !== null) {
                $log = $complaint->maintenanceLogs()->make([
                    'room_id' => $complaint->room_id,
                    ...$repair,
                ]);
                // property_id tidak fillable. Nilainya selalu kost milik kamar keluhan itu.
                $log->property_id = $complaint->room->property_id;
                $log->save();
            }
        });

        // Hanya saat baru selesai, bukan setiap kali keluhan yang sudah selesai disimpan ulang.
        if ($status === ComplaintStatus::Resolved && ! $wasResolved) {
            Notification::send($this->occupantsWhoCanSee($complaint), new ComplaintResolved($complaint->title));
        }
    }

    /**
     * Penghuni kamar itu yang punya akun dan boleh melihat keluhannya. Lewat ComplaintPolicy::view,
     * jadi penghuni baru tidak diberi tahu tentang keluhan penghuni lama kamar yang sama.
     *
     * @return Collection<int, User>
     */
    private function occupantsWhoCanSee(Complaint $complaint): Collection
    {
        return User::query()
            ->whereHas('currentOccupancy.tenancy', fn (Builder $query) => $query->where('room_id', $complaint->room_id))
            ->get()
            ->filter(fn (User $user) => $user->can('view', $complaint))
            ->values();
    }
}
