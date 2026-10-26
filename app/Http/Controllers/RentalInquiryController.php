<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRentalInquiryRequest;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RentalInquiryController extends Controller
{
    /**
     * Simpan permintaan survei dari halaman publik. Hanya isian yang lolos validasi
     * yang disimpan, dan status tidak bisa diisi dari luar (lihat RentalInquiry).
     */
    public function store(StoreRentalInquiryRequest $request): RedirectResponse
    {
        $property = Property::query()->oldest('id')->firstOrFail();

        $property->rentalInquiries()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Thanks! We will contact you to arrange a viewing.'),
        ]);

        return back();
    }
}
