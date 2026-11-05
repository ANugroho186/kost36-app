<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\LinkParcelReportRequest;
use App\Models\ParcelReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Pemilik kost mengurus laporan paket salah terima. Daftar laporannya ada di halaman paket
 * pemilik (Owner\ParcelController::index).
 */
class ParcelReportController extends Controller
{
    /**
     * Mencocokkan laporan dengan paket yang terdaftar. Sejak saat itu pemilik paket melihat tanda
     * "salah diterima" di halamannya, tanpa nama pelapor. Notifikasinya menyusul di Fase 3.
     */
    public function link(LinkParcelReportRequest $request, ParcelReport $parcelReport): RedirectResponse
    {
        $parcelReport->forceFill(['parcel_id' => $request->integer('parcel_id')])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Report linked to the parcel.')]);

        return to_route('owner.parcels.index');
    }

    /**
     * Paket sudah kembali ke pemiliknya, atau diurus di luar aplikasi kalau resinya tidak terdaftar.
     */
    public function resolve(ParcelReport $parcelReport): RedirectResponse
    {
        Gate::authorize('resolve', $parcelReport);

        $parcelReport->forceFill(['resolved_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Report resolved.')]);

        return to_route('owner.parcels.index');
    }
}
