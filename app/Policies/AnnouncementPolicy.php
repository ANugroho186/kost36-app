<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    /**
     * Pemilik dan semua penghuni boleh membuka daftar pengumuman (penghuni hanya kost-nya sendiri).
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->currentTenancy() !== null;
    }

    /**
     * Penghuni hanya membaca pengumuman kost tempat ia tinggal, dan hanya yang sudah tayang.
     */
    public function view(User $user, Announcement $announcement): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $user->currentTenancy()?->room->property_id === $announcement->property_id
            && ! $announcement->published_at->isFuture();
    }

    /**
     * Halaman kelola pengumuman, termasuk yang terjadwal dan kedaluwarsa. Penghuni membaca
     * lewat viewAny/view, yang hanya menampilkan pengumuman yang sudah tayang.
     */
    public function manage(User $user): bool
    {
        return $user->isOwner();
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->isOwner();
    }
}
