<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    /**
     * Daftar kamar lengkap (termasuk yang tersembunyi dan jumlah penghuni) hanya untuk pemilik.
     * Publik melihat kamar lewat halaman publik, bukan lewat Policy ini.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Room $room): bool
    {
        return $user->isOwner();
    }

    /**
     * Kamar yang punya data terkait tetap ditolak di controller, dengan pesan yang jelas.
     */
    public function delete(User $user, Room $room): bool
    {
        return $user->isOwner();
    }
}
