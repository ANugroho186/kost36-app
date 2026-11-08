<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParcelReportRequest;
use App\Models\User;
use App\Notifications\ParcelReportReceived;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Laporan paket salah terima dari sisi penghuni. Daftar laporannya ditampilkan di halaman
 * Parcels (ParcelController::index), jadi di sini hanya ada aksi melapor.
 */
class ParcelReportController extends Controller
{
    public function store(StoreParcelReportRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $user->parcelReports()->create($request->safe()->only('description'));
        $user->currentTenancy()?->room->property->owner->notify(new ParcelReportReceived);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Thank you. The owner will sort it out.')]);

        return to_route('parcels.index');
    }
}
