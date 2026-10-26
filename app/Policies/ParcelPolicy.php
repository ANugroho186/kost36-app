<?php

namespace App\Policies;

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use App\Models\User;

class ParcelPolicy
{
    /**
     * Pemilik kost melihat semua paket (tanpa nama barang), penghuni hanya paketnya sendiri.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->currentTenancy() !== null;
    }

    public function view(User $user, Parcel $parcel): bool
    {
        return $user->isOwner() || $this->ownsParcel($user, $parcel);
    }

    /**
     * Nama/isi barang hanya untuk penghuni pemilik paket. Pemilik kost sengaja tidak termasuk.
     */
    public function viewItemName(User $user, Parcel $parcel): bool
    {
        return $this->ownsParcel($user, $parcel);
    }

    /**
     * Penghuni yang sedang tinggal mendaftarkan paketnya sendiri. Pemilik kost tidak
     * mendaftarkan paket atas nama penghuni, karena isi barangnya bersifat pribadi.
     */
    public function create(User $user): bool
    {
        return $user->currentTenancy() !== null;
    }

    /**
     * Ubah data paket selama paket belum diterima siapa pun.
     */
    public function update(User $user, Parcel $parcel): bool
    {
        return $this->ownsParcel($user, $parcel) && $parcel->status === ParcelStatus::Waiting;
    }

    /**
     * Batalkan paket (misalnya pesanan dibatalkan) selama paket belum diterima siapa pun.
     */
    public function delete(User $user, Parcel $parcel): bool
    {
        return $this->ownsParcel($user, $parcel) && $parcel->status === ParcelStatus::Waiting;
    }

    /**
     * Pemilik kost mencentang "sudah di rumah saya".
     */
    public function markHeld(User $user, Parcel $parcel): bool
    {
        return $user->isOwner() && $parcel->status === ParcelStatus::Waiting;
    }

    /**
     * Pemilik paket mencentang "sudah saya terima", dari kurir langsung atau dari pemilik kost.
     */
    public function markCollected(User $user, Parcel $parcel): bool
    {
        return $this->ownsParcel($user, $parcel)
            && in_array($parcel->status, [ParcelStatus::Waiting, ParcelStatus::AtOwner], true);
    }

    private function ownsParcel(User $user, Parcel $parcel): bool
    {
        return $parcel->user_id === $user->id;
    }
}
