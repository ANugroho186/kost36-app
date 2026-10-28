<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRoommateConsentRequest;
use App\Models\Tenancy;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * "Bersedia berbagi kamar". Dipakai dari dashboard penghuni maupun halaman sewa pemilik,
 * jadi route-nya tidak berada di bawah /owner.
 */
class RoommateConsentController extends Controller
{
    public function __invoke(UpdateRoommateConsentRequest $request, Tenancy $tenancy): RedirectResponse
    {
        $accepts = $request->boolean('accepts_roommate');

        $tenancy->forceFill(['accepts_roommate' => $accepts])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $accepts
                ? __('The room is now open for one more person you know.')
                : __('The room is now marked as full.'),
        ]);

        return back();
    }
}
