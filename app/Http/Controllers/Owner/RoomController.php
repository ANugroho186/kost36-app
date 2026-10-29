<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\RoomRequest;
use App\Http\Requests\Owner\StoreRoomPhotoRequest;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomPhoto;
use App\Models\Tenancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    /**
     * Satu halaman untuk semua urusan kamar: tambah, ubah, foto, dan hapus dibuka sebagai modal.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Room::class);

        $occupying = Tenancy::query()->occupying()->with('activeOccupants')->get()->keyBy('room_id');

        return Inertia::render('owner/rooms/index', [
            'rooms' => Room::query()
                ->with('photos')
                ->orderByRaw('length(number), number')
                ->get()
                ->map(fn (Room $room): array => [
                    'id' => $room->id,
                    'number' => $room->number,
                    'capacity' => $room->capacity,
                    'facilities' => $room->facilities,
                    'isPublished' => $room->is_published,
                    'isOccupied' => $occupying->has($room->id),
                    'occupants' => $occupying->get($room->id)?->activeOccupants->count() ?? 0,
                    // false kalau kamar punya sewa, keluhan, perawatan, atau inventaris.
                    'canDelete' => ! $room->hasRelatedRecords(),
                    // Sudah terurut menurut sort_order (lihat Room::photos()).
                    'photos' => $room->photos
                        ->map(fn (RoomPhoto $photo): array => [
                            'id' => $photo->id,
                            'url' => Storage::disk('public')->url($photo->path),
                        ])
                        ->all(),
                ])
                ->all(),
            // Kamar baru selalu milik kost yang profilnya sudah diisi.
            'canAddRooms' => Property::query()->exists(),
            'maxPhotos' => StoreRoomPhotoRequest::MAX_PHOTOS_PER_ROOM,
        ]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $property = Property::query()->oldest('id')->first();

        if ($property === null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Set up the property profile first.')]);

            return to_route('owner.property.edit');
        }

        $property->rooms()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Room added.')]);

        return to_route('owner.rooms.index');
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
