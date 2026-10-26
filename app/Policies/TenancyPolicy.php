<?php

namespace App\Policies;

use App\Models\Tenancy;
use App\Models\User;

class TenancyPolicy
{
    /**
     * Daftar semua sewa hanya untuk pemilik.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Penghuni hanya melihat sewa kamarnya sendiri, bersama teman sekamarnya.
     */
    public function view(User $user, Tenancy $tenancy): bool
    {
        return $user->isOwner() || $user->occupies($tenancy);
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Termasuk keputusan pemilik: perpanjang, nonaktifkan, dan pengosongan.
     */
    public function update(User $user, Tenancy $tenancy): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, Tenancy $tenancy): bool
    {
        return $user->isOwner();
    }
}
