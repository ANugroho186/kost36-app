<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Tenancies\StartTenancy;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StartTenancyRequest;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TenancyController extends Controller
{
    /**
     * Sewa yang sedang menempati kamar (active/inactive). Riwayat sewa ended ditampilkan nanti.
     * Form sewa baru dibuka sebagai modal di halaman ini.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Tenancy::class);

        $tenancies = Tenancy::query()
            ->occupying()
            ->with(['room', 'activeOccupants'])
            ->get()
            ->sortBy(fn (Tenancy $tenancy): string => $tenancy->room->number, SORT_NATURAL);

        return Inertia::render('owner/tenancies/index', [
            'tenancies' => $tenancies
                ->map(fn (Tenancy $tenancy): array => [
                    'id' => $tenancy->id,
                    'roomNumber' => $tenancy->room->number,
                    'status' => $tenancy->status->value,
                    'paidUntil' => $tenancy->paid_until->toDateString(),
                    'occupants' => $tenancy->activeOccupants->pluck('nickname')->filter()->values()->all(),
                    'acceptsRoommate' => $tenancy->accepts_roommate,
                ])
                ->values()
                ->all(),
            // Pilihan kamar di form sewa baru: hanya yang sedang kosong.
            'availableRooms' => Room::query()
                ->whereNotIn('id', $tenancies->pluck('room_id'))
                ->orderByRaw('length(number), number')
                ->get()
                ->map(fn (Room $room): array => ['id' => $room->id, 'number' => $room->number])
                ->all(),
            'today' => CarbonImmutable::today()->toDateString(),
        ]);
    }

    public function store(StartTenancyRequest $request, StartTenancy $startTenancy): RedirectResponse
    {
        $tenancy = $startTenancy->handle(
            Room::query()->findOrFail($request->integer('room_id')),
            CarbonImmutable::parse($request->string('start_date')->toString()),
            $request->integer('months_paid'),
            $request->string('nickname')->toString(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tenancy started.')]);

        return to_route('owner.tenancies.show', $tenancy);
    }

    /**
     * Halaman pemilik, jadi dicek dengan `update` (pemilik saja), bukan `view` yang juga
     * mengizinkan penghuni melihat sewanya sendiri dari dashboard mereka.
     */
    public function show(Tenancy $tenancy): Response
    {
        Gate::authorize('update', $tenancy);

        $tenancy->load(['room', 'activeOccupants.user']);
        $occupants = $tenancy->activeOccupants->count();
        $hasSpace = $occupants < $tenancy->room->capacity;

        return Inertia::render('owner/tenancies/show', [
            'tenancy' => [
                'id' => $tenancy->id,
                'roomNumber' => $tenancy->room->number,
                'capacity' => $tenancy->room->capacity,
                'status' => $tenancy->status->value,
                'startDate' => $tenancy->start_date->toDateString(),
                'paidUntil' => $tenancy->paid_until->toDateString(),
                'vacateBy' => $tenancy->vacate_by?->toDateString(),
                'acceptsRoommate' => $tenancy->accepts_roommate,
                'occupants' => $tenancy->activeOccupants
                    ->map(fn (TenancyOccupant $occupant): array => [
                        'id' => $occupant->id,
                        'nickname' => $occupant->nickname,
                        'movedInAt' => $occupant->moved_in_at->toDateString(),
                        'hasAccount' => $occupant->user_id !== null,
                        // Hanya username: email dan kontak notifikasi tidak dibutuhkan di sini.
                        'username' => $occupant->user?->username,
                    ])
                    ->all(),
            ],
            'canManageAccounts' => Gate::allows('manageAccounts', $tenancy),
            'canAddOccupant' => Gate::allows('addOccupant', $tenancy)
                && $hasSpace
                && ($occupants === 0 || $tenancy->accepts_roommate),
            'canToggleRoommate' => Gate::allows('toggleRoommate', $tenancy) && $hasSpace,
            'today' => CarbonImmutable::today()->toDateString(),
        ]);
    }
}
