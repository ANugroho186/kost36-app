<?php

namespace App\Policies;

use App\Models\ParcelReport;
use App\Models\User;

class ParcelReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner();
    }

    /**
     * Pelapor boleh memantau laporannya sendiri. Detail paket yang ditautkan tetap tidak
     * boleh ditampilkan kepadanya.
     */
    public function view(User $user, ParcelReport $report): bool
    {
        return $user->isOwner() || $report->reporter_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTenancy() !== null;
    }

    /**
     * Pemilik kost menautkan laporan ke paket yang cocok, lalu pemilik paket diberi notifikasi.
     */
    public function link(User $user, ParcelReport $report): bool
    {
        return $user->isOwner() && $report->resolved_at === null;
    }

    public function resolve(User $user, ParcelReport $report): bool
    {
        return $user->isOwner() && $report->resolved_at === null;
    }
}
