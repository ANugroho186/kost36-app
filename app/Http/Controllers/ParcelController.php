<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParcelRequest;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Paket pesanan dari sisi penghuni: hanya paket miliknya sendiri, termasuk nama barangnya.
 * Teman sekamar dan pemilik kost tidak melihat isi halaman ini.
 */
class ParcelController extends Controller
{
    public function index(#[CurrentUser] User $user): Response|RedirectResponse
    {
        // Pemilik kost melihat paket semua penghuni (tanpa nama barang) di halamannya sendiri.
        if ($user->isOwner()) {
            return to_route('owner.parcels.index');
        }

        return Inertia::render('tenant/parcels/index', [
            'parcels' => $user->parcels()
                // Yang belum diterima di atas, lalu terbaru lebih dulu.
                ->orderByRaw('collected_at IS NOT NULL')
                ->latest()
                ->latest('id')
                ->get()
                ->map(fn (Parcel $parcel): array => [
                    'id' => $parcel->id,
                    'trackingNumber' => $parcel->tracking_number,
                    // Daftar ini memang hanya paket milik user yang login, tapi tetap dicek lewat
                    // Policy: nama barang tidak boleh sampai ke siapa pun selain pemilik paket.
                    'itemName' => Gate::allows('viewItemName', $parcel) ? $parcel->item_name : null,
                    'expectedDate' => $parcel->expected_date?->toDateString(),
                    'status' => $parcel->status->value,
                ])
                ->all(),
            // Hanya penghuni yang sedang tinggal. Pemilik kost tidak mendaftarkan paket.
            'canRegister' => Gate::allows('create', Parcel::class),
        ]);
    }

    public function store(ParcelRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $user->parcels()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Parcel registered.')]);

        return to_route('parcels.index');
    }

    public function update(ParcelRequest $request, Parcel $parcel): RedirectResponse
    {
        $parcel->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Parcel updated.')]);

        return to_route('parcels.index');
    }

    /**
     * Membatalkan paket yang belum diterima, misalnya karena pesanannya dibatalkan.
     */
    public function destroy(Parcel $parcel): RedirectResponse
    {
        Gate::authorize('delete', $parcel);

        $parcel->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Parcel removed.')]);

        return to_route('parcels.index');
    }

    /**
     * "Sudah saya terima": langsung dari kurir, atau diambil dari pemilik kost.
     */
    public function markCollected(Parcel $parcel): RedirectResponse
    {
        Gate::authorize('markCollected', $parcel);

        $parcel->forceFill(['collected_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Marked as received.')]);

        return to_route('parcels.index');
    }
}
