<?php

namespace App\Support;

use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Password sementara dari pemilik untuk penghuni, dengan format yang dipilih pemilik:
 * bulan-tanggal-kamar-idAkun-KODE-tahun, misalnya "07-15-3-9-K7P4QZ-2026".
 *
 * Hanya KODE yang rahasia. Bagian lain bisa ditebak teman sekamar atau tetangga, jadi KODE
 * dibuat acak dengan random_int (aman untuk kriptografi). 31^6 ≈ 887 juta kemungkinan; dengan
 * batas 5 percobaan login per menit, menebaknya butuh ratusan tahun.
 */
class TemporaryPassword
{
    // Tanpa karakter yang mudah tertukar saat dibacakan atau diketik: 0/O, 1/I/L.
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const CODE_LENGTH = 6;

    public static function generate(CarbonImmutable $movedInAt, Room $room, User $user): string
    {
        return implode('-', [
            $movedInAt->format('m'),
            $movedInAt->format('d'),
            $room->number,
            $user->id,
            self::randomCode(),
            $movedInAt->format('Y'),
        ]);
    }

    private static function randomCode(): string
    {
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }
}
