<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Paket semua penghuni dari sisi pemilik kost: no. resi, nickname, kamar, perkiraan tiba, dan
 * status. Nama/isi barang sengaja tidak pernah diambil di sini, jadi tidak mungkin terkirim.
 */
class ParcelController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAll', Parcel::class);

        return Inertia::render('owner/parcels/index', [
            'parcels' => Parcel::query()
                // Tanpa item_name: kolom itu tidak dibaca dari database sama sekali.
                ->select(['id', 'user_id', 'tracking_number', 'expected_date', 'held_by_owner_at', 'collected_at'])
                ->with('user.currentOccupancy.tenancy.room')
                // Yang belum diterima di atas, diurutkan dari perkiraan tiba paling dekat.
                ->orderByRaw('collected_at IS NOT NULL')
                ->orderByRaw('expected_date IS NULL')
                ->orderBy('expected_date')
                ->latest('id')
                ->get()
                ->map(function (Parcel $parcel): array {
                    $occupancy = $parcel->user->currentOccupancy;

                    return [
                        'id' => $parcel->id,
                        'trackingNumber' => $parcel->tracking_number,
                        'recipient' => $occupancy->nickname ?? $parcel->user->name,
                        'roomNumber' => $occupancy?->tenancy->room->number,
                        'expectedDate' => $parcel->expected_date?->toDateString(),
                        'status' => $parcel->status->value,
                    ];
                })
                ->all(),
        ]);
    }

    /**
     * "Sudah di rumah saya": pemilik kost menerima paket karena penghuninya sedang tidak ada.
     */
    public function markHeld(Parcel $parcel): RedirectResponse
    {
        Gate::authorize('markHeld', $parcel);

        $parcel->forceFill(['held_by_owner_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Parcel marked as at your place.')]);

        return to_route('owner.parcels.index');
    }
}
