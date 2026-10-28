<?php

namespace App\Actions\Tenancies;

use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menambah penghuni ke sewa yang sedang berjalan (misalnya teman sekamar).
 *
 * Aturannya: masih ada tempat menurut rooms.capacity, dan kalau sudah ada penghuni,
 * sewa itu harus "bersedia berbagi kamar". Izin itu berlaku untuk satu kali penambahan.
 */
class AddOccupant
{
    /**
     * @throws ValidationException
     */
    public function handle(Tenancy $tenancy, string $nickname, CarbonImmutable $movedInAt): TenancyOccupant
    {
        return DB::transaction(function () use ($tenancy, $nickname, $movedInAt): TenancyOccupant {
            // Kunci baris sewa sampai transaksi selesai. Dua permintaan bersamaan harus antre,
            // jadi keduanya tidak bisa sama-sama lolos cek kapasitas dan membuat kamar berisi 3 orang.
            $locked = Tenancy::query()->with('room')->lockForUpdate()->findOrFail($tenancy->id);
            $occupants = $locked->activeOccupants()->count();

            if ($occupants >= $locked->room->capacity) {
                throw ValidationException::withMessages(['nickname' => __('This room is already full.')]);
            }

            if ($occupants > 0 && ! $locked->accepts_roommate) {
                throw ValidationException::withMessages([
                    'nickname' => __('The current occupant has not agreed to share the room yet.'),
                ]);
            }

            $occupant = $locked->occupants()->create([
                'nickname' => $nickname,
                'moved_in_at' => $movedInAt,
            ]);

            $locked->forceFill(['accepts_roommate' => false])->save();

            return $occupant;
        });
    }
}
