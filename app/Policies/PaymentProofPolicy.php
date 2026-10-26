<?php

namespace App\Policies;

use App\Enums\PaymentProofStatus;
use App\Models\PaymentProof;
use App\Models\Tenancy;
use App\Models\User;

class PaymentProofPolicy
{
    /**
     * Daftar semua bukti (untuk diverifikasi) hanya untuk pemilik.
     */
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Status bukti terlihat oleh semua penghuni kamar itu, termasuk teman sekamar.
     */
    public function view(User $user, PaymentProof $proof): bool
    {
        return $user->isOwner() || $user->occupies($proof->tenancy);
    }

    /**
     * Gambar bukti biasanya memuat nama dan rekening bank, jadi hanya untuk pemilik dan pengunggahnya.
     */
    public function viewFile(User $user, PaymentProof $proof): bool
    {
        return $user->isOwner() || $proof->uploaded_by === $user->id;
    }

    /**
     * Pemilik boleh mencatat pembayaran atas nama penghuni tanpa akun.
     */
    public function create(User $user, Tenancy $tenancy): bool
    {
        return $user->isOwner() || $user->occupies($tenancy);
    }

    /**
     * Setujui atau tolak. Hanya bukti yang masih menunggu yang bisa ditinjau.
     */
    public function review(User $user, PaymentProof $proof): bool
    {
        return $user->isOwner() && $proof->status === PaymentProofStatus::Pending;
    }
}
