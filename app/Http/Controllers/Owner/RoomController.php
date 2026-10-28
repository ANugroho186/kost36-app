<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\RoomRequest;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Room::class);

        $occupying = Tenancy::query()->occupying()->with('activeOccupants')->get()->keyBy('room_id');

        return Inertia::render('owner/rooms/index', [
            'rooms' => Room::query()
                ->orderByRaw('length(number), number')
                ->get()
                ->map(fn (Room $room): array => [
                    'id' => $room->id,
                    'number' => $room->number,
                    'capacity' => $room->capacity,
                    'isPublished' => $room->is_published,
                    'isOccupied' => $occupying->has($room->id),
                    'occupants' => $occupying->get($room->id)?->activeOccupants->count() ?? 0,
                ])
                ->all(),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        Gate::authorize('create', Room::class);

        if (! Property::query()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Set up the property profile first.')]);

            return to_route('owner.property.edit');
        }

        return Inertia::render('owner/rooms/create');
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $property = Property::query()->oldest('id')->first();

        if ($property === null) {
            return to_route('owner.property.edit');
        }

        $property->rooms()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room added.')]);

        return to_route('owner.rooms.index');
    }

    public function edit(Room $room): Response
    {
        Gate::authorize('update', $room);

        return Inertia::render('owner/rooms/edit', [
            'room' => [
                'id' => $room->id,
                'number' => $room->number,
                'capacity' => $room->capacity,
                'facilities' => $room->facilities,
                'isPublished' => $room->is_published,
            ],
            'occupants' => $room->activeOccupantCount(),
            'canDelete' => ! $room->hasRelatedRecords(),
        ]);
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room updated.')]);

        return to_route('owner.rooms.index');
    }

    /**
     * Kamar dengan riwayat tidak dihapus. Pemilik disarankan menyembunyikannya dari halaman publik.
     */
    public function destroy(Room $room): RedirectResponse
    {
        Gate::authorize('delete', $room);

        if ($room->hasRelatedRecords()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This room has history and cannot be deleted. Hide it from the public page instead.'),
            ]);

            return back();
        }

        // Cascade hanya menghapus baris foto di database. Filenya dihapus di sini.
        Storage::disk('public')->delete($room->photos->pluck('path')->all());
        $room->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room deleted.')]);

        return to_route('owner.rooms.index');
    }
}
