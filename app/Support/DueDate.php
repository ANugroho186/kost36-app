<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Tanggal bayar selalu jatuh pada tanggal yang sama dengan tanggal masuk (tanggal patokan).
 * Kalau bulan itu lebih pendek, dipakai hari terakhir bulan itu.
 *
 * Contoh tanggal masuk 31 Jan: +1 bulan = 28 Feb, +2 bulan = 31 Mar. Dari 28 Feb +1 bulan
 * kembali menjadi 31 Mar, tidak "terbawa" menjadi 28 Mar seperti addMonthsNoOverflow berantai.
 */
class DueDate
{
    public static function advance(CarbonImmutable $from, int $months, int $anchorDay): CarbonImmutable
    {
        $month = $from->startOfMonth()->addMonths($months);

        return $month->setDay(min($anchorDay, $month->daysInMonth));
    }
}
