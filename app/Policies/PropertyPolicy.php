<?php

namespace App\Policies;

use App\Models\User;

class PropertyPolicy
{
    /**
     * Mengubah profil kost (nama, alamat, deskripsi, aturan) hanya untuk pemilik.
     */
    public function manage(User $user): bool
    {
        return $user->isOwner();
    }
}
