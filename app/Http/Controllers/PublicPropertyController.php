<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Room;
use App\Models\RoomPhoto;
use App\Models\Tenancy;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicPropertyController extends Controller
{
    /**
     * Halaman publik kost. Semua yang dikirim ke halaman ini bisa dilihat siapa pun,
     * jadi datanya dipilih satu per satu dan tidak memuat data penghuni sama sekali.
     */
    public function __invoke(): Response
    {
        $property = Property::query()->oldest('id')->first();

        // Status kamar dihitung, tidak disimpan: terisi kalau punya sewa active/inactive.
        $occupiedRoomIds = Tenancy::query()->occupying()->pluck('room_id');

        $rooms = $property === null ? collect() : $property->rooms()
            ->where('is_published', true)
            ->with('photos')
            // Urutan alami: "2" sebelum "10".
            ->orderByRaw('length(number), number')
            ->get()
            ->map(fn (Room $room): array => [
                'number' => $room->number,
                'capacity' => $room->capacity,
                'facilities' => $room->facilities,
                'isAvailable' => ! $occupiedRoomIds->contains($room->id),
                'photos' => $room->photos
                    ->map(fn (RoomPhoto $photo): string => Storage::disk('public')->url($photo->path))
                    ->all(),
            ]);

        return Inertia::render('public/home', [
            'property' => $property === null ? null : [
                'name' => $property->name,
                'address' => $property->address,
                'description' => $property->description,
                'rules' => $property->rules,
            ],
            'rooms' => $rooms->values()->all(),
            'summary' => [
                'available' => $rooms->where('isAvailable', true)->count(),
                'total' => $rooms->count(),
            ],
        ]);
    }
}
