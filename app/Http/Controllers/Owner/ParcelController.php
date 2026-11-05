<?php

namespace App\Http\Controllers\Owner;

use App\Enums\ParcelReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\ParcelReport;
use Illuminate\Database\Eloquent\Collection;
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
        Gate::authorize('viewAny', ParcelReport::class);

        $parcels = Parcel::query()
            // Tanpa item_name: kolom itu tidak dibaca dari database sama sekali.
            ->select(['id', 'user_id', 'tracking_number', 'expected_date', 'held_by_owner_at', 'collected_at'])
            ->with('user.currentOccupancy.tenancy.room')
            // Yang belum diterima di atas, diurutkan dari perkiraan tiba paling dekat.
            ->orderByRaw('collected_at IS NOT NULL')
            ->orderByRaw('expected_date IS NULL')
            ->orderBy('expected_date')
            ->latest('id')
            ->get();

        $reports = ParcelReport::query()
            ->with('reporter.currentOccupancy.tenancy.room')
            // Yang belum selesai di atas, lalu terbaru.
            ->orderByRaw('resolved_at IS NOT NULL')
            ->latest()
            ->latest('id')
            ->get();

        return Inertia::render('owner/parcels/index', [
            'parcels' => $parcels
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
            'reports' => $reports
                ->map(function (ParcelReport $report) use ($parcels): array {
                    // Pemilik kost perlu tahu siapa yang memegang paketnya, supaya bisa mengambilnya.
                    $occupancy = $report->reporter?->currentOccupancy;

                    return [
                        'id' => $report->id,
                        'description' => $report->description,
                        'reportedAt' => $report->created_at?->toDateString(),
                        'status' => $report->status->value,
                        'reporter' => $occupancy->nickname ?? $report->reporter?->name,
                        'reporterRoom' => $occupancy?->tenancy->room->number,
                        'parcelId' => $report->parcel_id,
                        'suggestedParcelId' => $report->status === ParcelReportStatus::Open
                            ? $this->suggestedParcelId($report, $parcels)
                            : null,
                    ];
                })
                ->all(),
        ]);
    }

    /**
     * Paket yang no. resinya tertulis di keterangan laporan, sebagai saran pencocokan. Keterangan
     * dirapikan dengan cara yang sama seperti resi (spasi dibuang, huruf kapital). Hanya paket
     * yang belum diterima dan bukan milik pelapor sendiri. Resi terpanjang didahulukan, supaya
     * resi pendek yang kebetulan terkandung di resi lain tidak menang.
     *
     * @param  Collection<int, Parcel>  $parcels
     */
    private function suggestedParcelId(ParcelReport $report, Collection $parcels): ?int
    {
        $text = strtoupper((string) preg_replace('/\s+/', '', $report->description));

        return $parcels
            ->filter(fn (Parcel $parcel) => $parcel->collected_at === null
                && $parcel->user_id !== $report->reporter_id
                && str_contains($text, $parcel->tracking_number))
            ->sortByDesc(fn (Parcel $parcel) => strlen($parcel->tracking_number))
            ->first()
            ?->id;
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
