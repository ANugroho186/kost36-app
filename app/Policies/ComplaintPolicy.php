<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;

class ComplaintPolicy
{
    /**
     * Daftar semua keluhan hanya untuk pemilik.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Penghuni melihat keluhan kamarnya (termasuk milik teman sekamar), tapi hanya yang dibuat
     * selama sewa yang sedang berjalan. Keluhan penghuni lama bisa memuat cerita pribadi mereka.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        $tenancy = $user->currentTenancy();

        return $tenancy !== null && $complaint->wasReportedDuring($tenancy);
    }

    /**
     * Pemilik boleh mencatat keluhan atas nama penghuni tanpa akun.
     */
    public function create(User $user, Room $room): bool
    {
        return $user->isOwner() || $user->currentTenancy()?->room_id === $room->id;
    }

    /**
     * Termasuk mengubah status (diproses, selesai).
     */
    public function update(User $user, Complaint $complaint): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, Complaint $complaint): bool
    {
        return $user->isOwner();
    }
}
