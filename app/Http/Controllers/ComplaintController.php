<?php

namespace App\Http\Controllers;

use App\Actions\Complaints\ReportComplaint;
use App\Http\Requests\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\ComplaintPhoto;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Keluhan dari sisi penghuni. Semuanya tentang kamar penghuni yang login, jadi tidak ada
 * {room} di URL yang bisa diganti. Pemilik mengelola keluhan dari halamannya sendiri di bawah /owner.
 */
class ComplaintController extends Controller
{
    /**
     * Keluhan kamarnya selama sewa yang sedang berjalan, termasuk yang dilaporkan teman sekamar.
     */
    public function index(#[CurrentUser] User $user): Response
    {
        $tenancy = $user->currentTenancy();

        $complaints = $tenancy === null ? [] : Complaint::query()
            ->reportedDuring($tenancy)
            ->with('photos')
            ->latest()
            ->latest('id')
            ->get()
            // Tanpa pelapor: yang penting bagi penghuni adalah masalah kamarnya dan statusnya.
            ->map(fn (Complaint $complaint) => [
                'id' => $complaint->id,
                'title' => $complaint->title,
                'description' => $complaint->description,
                'status' => $complaint->status->value,
                'reportedAt' => $complaint->created_at?->toDateString(),
                'resolvedAt' => $complaint->resolved_at?->toDateString(),
                'photos' => $complaint->photos->map(fn (ComplaintPhoto $photo) => [
                    'id' => $photo->id,
                    'url' => route('complaints.photos.show', [$complaint, $photo]),
                ])->all(),
            ])
            ->all();

        return Inertia::render('tenant/complaints/index', [
            'roomNumber' => $tenancy?->room->number,
            'complaints' => $complaints,
        ]);
    }

    public function create(#[CurrentUser] User $user): Response
    {
        $room = $user->currentTenancy()?->room;
        abort_if($room === null, 403);
        Gate::authorize('create', [Complaint::class, $room]);

        return Inertia::render('tenant/complaints/create', [
            'roomNumber' => $room->number,
            'maxPhotos' => ReportComplaint::MAX_PHOTOS,
        ]);
    }

    public function store(StoreComplaintRequest $request, #[CurrentUser] User $user, ReportComplaint $report): RedirectResponse
    {
        $report->handle(
            $request->room(),
            $user,
            ['title' => $request->string('title')->value(), 'description' => $request->string('description')->value()],
            Arr::wrap($request->file('photos')),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Complaint sent. The owner will follow it up.')]);

        return to_route('complaints.index');
    }
}
