<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreRoomPhotoRequest;
use App\Models\Room;
use App\Models\RoomPhoto;
use App\Support\ImageUpload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Foto kamar di disk public. Route-nya memakai scopeBindings(), jadi {photo} hanya cocok
 * kalau foto itu memang milik {room} di URL. Selain itu hasilnya 404.
 */
class RoomPhotoController extends Controller
{
    public function store(StoreRoomPhotoRequest $request, Room $room, ImageUpload $images): RedirectResponse
    {
        $last = $room->photos()->max('sort_order');
        $nextOrder = is_numeric($last) ? (int) $last + 1 : 0;

        // Foto baru ditaruh di urutan terakhir. Validasi sudah memastikan setiap item adalah gambar.
        foreach (Arr::wrap($request->file('photos')) as $file) {
            $room->photos()->create([
                'path' => $images->store($file, 'rooms'),
                'sort_order' => $nextOrder++,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photos uploaded.')]);

        return to_route('owner.rooms.edit', $room);
    }

    /**
     * Jadikan foto ini foto pertama (yang tampil di halaman publik). Urutan foto lain tetap.
     */
    public function cover(Room $room, RoomPhoto $photo): RedirectResponse
    {
        Gate::authorize('update', $room);

        DB::transaction(function () use ($room, $photo): void {
            $photo->update(['sort_order' => 0]);

            $room->photos()->whereKeyNot($photo->id)->get()
                ->each(fn (RoomPhoto $other, int $index) => $other->update(['sort_order' => $index + 1]));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Cover photo updated.')]);

        return to_route('owner.rooms.edit', $room);
    }

    public function destroy(Room $room, RoomPhoto $photo): RedirectResponse
    {
        Gate::authorize('update', $room);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo deleted.')]);

        return to_route('owner.rooms.edit', $room);
    }
}
