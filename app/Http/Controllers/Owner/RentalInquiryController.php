<?php

namespace App\Http\Controllers\Owner;

use App\Enums\RentalInquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateRentalInquiryStatusRequest;
use App\Models\RentalInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Permintaan survei dari form publik, dari sisi pemilik kost.
 */
class RentalInquiryController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', RentalInquiry::class);

        return Inertia::render('owner/rental-inquiries/index', [
            'inquiries' => RentalInquiry::query()
                // Yang baru di atas, lalu yang sudah dihubungi, lalu yang selesai. Terbaru lebih dulu.
                ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [
                    RentalInquiryStatus::New->value,
                    RentalInquiryStatus::Contacted->value,
                ])
                ->latest()
                ->latest('id')
                ->get()
                ->map(fn (RentalInquiry $inquiry): array => [
                    'id' => $inquiry->id,
                    'name' => $inquiry->name,
                    'contact' => $inquiry->contact,
                    'preferredDate' => $inquiry->preferred_date?->toDateString(),
                    'message' => $inquiry->message,
                    'status' => $inquiry->status->value,
                    'receivedAt' => $inquiry->created_at?->format('Y-m-d\TH:i'),
                    // Supaya pemilik tahu kapan data calon penyewa ini hilang dengan sendirinya.
                    'deletesOn' => $inquiry->done_at?->addDays(RentalInquiry::RETENTION_DAYS)->toDateString(),
                ])
                ->all(),
        ]);
    }

    public function update(UpdateRentalInquiryStatusRequest $request, RentalInquiry $rentalInquiry): RedirectResponse
    {
        $status = RentalInquiryStatus::from($request->string('status')->value());

        $rentalInquiry->forceFill([
            'status' => $status,
            // done_at menjadi dasar penghapusan otomatis, jadi hanya terisi selama statusnya selesai.
            'done_at' => $status === RentalInquiryStatus::Done ? ($rentalInquiry->done_at ?? now()) : null,
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Viewing request updated.')]);

        return to_route('owner.rental-inquiries.index');
    }

    public function destroy(RentalInquiry $rentalInquiry): RedirectResponse
    {
        Gate::authorize('delete', $rentalInquiry);

        $rentalInquiry->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Viewing request deleted.')]);

        return to_route('owner.rental-inquiries.index');
    }
}
