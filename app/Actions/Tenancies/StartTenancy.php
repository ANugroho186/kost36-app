<?php

namespace App\Actions\Tenancies;

use App\Models\Room;
use App\Models\Tenancy;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Memulai sewa baru di kamar kosong, sekaligus mencatat penghuni pertamanya.
 */
class StartTenancy
{
    /**
     * @throws ValidationException
     */
    public function handle(Room $room, CarbonImmutable $startDate, int $monthsPaid, string $nickname): Tenancy
    {
        try {
            return DB::transaction(function () use ($room, $startDate, $monthsPaid, $nickname): Tenancy {
                if ($room->tenancies()->occupying()->exists()) {
                    throw $this->roomOccupied();
                }

                // paid_until adalah tanggal bayar berikutnya: masuk 1 Jan + 1 bulan = 1 Feb.
                // NoOverflow: masuk 31 Jan + 1 bulan = 28 Feb, bukan 3 Maret.
                $tenancy = $room->tenancies()->create([
                    'start_date' => $startDate,
                    'paid_until' => $startDate->addMonthsNoOverflow($monthsPaid),
                ]);

                $tenancy->occupants()->create([
                    'nickname' => $nickname,
                    'moved_in_at' => $startDate,
                ]);

                return $tenancy;
            });
        } catch (UniqueConstraintViolationException) {
            // Dua permintaan bersamaan sama-sama lolos cek di atas. Partial unique index di
            // database menolak yang kedua, lalu diubah menjadi pesan validasi biasa.
            throw $this->roomOccupied();
        }
    }

    private function roomOccupied(): ValidationException
    {
        return ValidationException::withMessages([
            'room_id' => __('This room is already occupied.'),
        ]);
    }
}
