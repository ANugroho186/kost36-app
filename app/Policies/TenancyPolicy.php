<?php

namespace App\Policies;

use App\Enums\TenancyStatus;
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

    /**
     * Menyalakan atau mematikan "bersedia berbagi kamar". Hanya selama sewa aktif: kamar yang
     * sedang menunggu pengosongan tidak ditawarkan untuk orang kedua.
     */
    public function toggleRoommate(User $user, Tenancy $tenancy): bool
    {
        return $tenancy->status === TenancyStatus::Active
            && ($user->isOwner() || $user->occupies($tenancy));
    }

    /**
     * Menambah penghuni ke sewa yang sedang aktif. Syarat kapasitas dan izin berbagi dicek di
     * AddOccupant, supaya pemilik mendapat pesan validasi yang jelas, bukan halaman 403.
     */
    public function addOccupant(User $user, Tenancy $tenancy): bool
    {
        return $user->isOwner() && $tenancy->status === TenancyStatus::Active;
    }

    /**
     * Membuatkan akun login untuk penghuni di sewa ini. Akun penghuni dibuat oleh pemilik,
     * tidak ada registrasi publik.
     */
    public function manageAccounts(User $user, Tenancy $tenancy): bool
    {
        return $user->isOwner();
    }
}
