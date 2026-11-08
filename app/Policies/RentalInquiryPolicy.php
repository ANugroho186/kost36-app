<?php

namespace App\Policies;

use App\Models\RentalInquiry;
use App\Models\User;

/**
 * Permintaan survei berisi nama dan kontak calon penyewa, jadi hanya pemilik kost yang boleh
 * membacanya. Membuatnya lewat form publik, tanpa login.
 */
class RentalInquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Mengubah status: baru → sudah dihubungi → selesai, atau dibuka lagi.
     */
    public function update(User $user, RentalInquiry $inquiry): bool
    {
        return $user->isOwner();
    }

    /**
     * Misalnya spam. Tidak perlu menunggu penghapusan otomatis 30 hari setelah selesai.
     */
    public function delete(User $user, RentalInquiry $inquiry): bool
    {
        return $user->isOwner();
    }
}
