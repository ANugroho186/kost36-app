<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Enums\PaymentProofStatus;
use App\Enums\RentalInquiryStatus;
use App\Models\Complaint;
use App\Models\Parcel;
use App\Models\PaymentProof;
use App\Models\RentalInquiry;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Satu alamat /dashboard, isinya berbeda per peran.
     */
    public function __invoke(#[CurrentUser] User $user): Response
    {
        return $user->isOwner() ? $this->owner() : $this->tenant($user);
    }

    /**
     * Ringkasan untuk pemilik: hanya angka, tanpa data pribadi siapa pun.
     */
    private function owner(): Response
    {
        return Inertia::render('owner/dashboard', [
            'rooms' => [
                'total' => Room::query()->count(),
                // Satu kamar maksimal satu sewa occupying (partial unique index).
                'occupied' => Tenancy::query()->occupying()->count(),
            ],
            'pendingPaymentProofs' => PaymentProof::query()->where('status', PaymentProofStatus::Pending)->count(),
            'openComplaints' => Complaint::query()->where('status', '!=', ComplaintStatus::Resolved)->count(),
            'parcels' => [
                'waiting' => Parcel::query()->whereNull('held_by_owner_at')->whereNull('collected_at')->count(),
                'atOwner' => Parcel::query()->whereNotNull('held_by_owner_at')->whereNull('collected_at')->count(),
            ],
            'newRentalInquiries' => RentalInquiry::query()->where('status', RentalInquiryStatus::New)->count(),
        ]);
    }

    /**
     * Ringkasan untuk penghuni: kamarnya sendiri dan nickname teman sekamar.
     */
    private function tenant(User $user): Response
    {
        $tenancy = $user->currentTenancy();

        if ($tenancy === null) {
            return Inertia::render('tenant/dashboard', [
                'tenancy' => null,
                'hasTemporaryPassword' => $user->has_temporary_password,
                'announcements' => [],
            ]);
        }

        Gate::authorize('view', $tenancy);

        $hasSpace = $tenancy->activeOccupants()->count() < $tenancy->room->capacity;

        return Inertia::render('tenant/dashboard', [
            // Pengingat (tidak wajib) untuk mengganti password sementara dari pemilik.
            'hasTemporaryPassword' => $user->has_temporary_password,
            'tenancy' => [
                'id' => $tenancy->id,
                'roomNumber' => $tenancy->room->number,
                'status' => $tenancy->status->value,
                'paidUntil' => $tenancy->paid_until->toDateString(),
                'vacateBy' => $tenancy->vacate_by?->toDateString(),
                'roommates' => $tenancy->activeOccupants()
                    ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', '!=', $user->id))
                    ->pluck('nickname')
                    ->filter()
                    ->values()
                    ->all(),
                'acceptsRoommate' => $tenancy->accepts_roommate,
                // Tombol "bersedia berbagi kamar" hanya muncul kalau masih ada tempat dan sewa aktif.
                'canToggleRoommate' => $hasSpace && Gate::allows('toggleRoommate', $tenancy),
            ],
            // Isinya sama dengan halaman Announcements, hanya dibatasi 3 terbaru.
            'announcements' => AnnouncementController::liveFor($tenancy, 3),
        ]);
    }
}
