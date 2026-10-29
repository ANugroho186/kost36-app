<?php

namespace App\Actions\Complaints;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

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
    }
}
