<?php

use App\Support\DueDate;
use Carbon\CarbonImmutable;

test('the due date keeps the move-in day as its anchor', function (string $from, int $months, int $anchorDay, string $expected) {
    $due = DueDate::advance(CarbonImmutable::parse($from), $months, $anchorDay);

    expect($due->toDateString())->toBe($expected);
})->with([
    'masuk 1 Jan, 1 bulan' => ['2026-01-01', 1, 1, '2026-02-01'],
    'masuk 31 Jan, 2 bulan' => ['2026-01-31', 2, 31, '2026-03-31'],
    'masuk 31 Jan, 1 bulan: Februari pendek' => ['2026-01-31', 1, 31, '2026-02-28'],
    'tahun kabisat' => ['2028-01-31', 1, 31, '2028-02-29'],
    // Pembayaran berikutnya dihitung dari paid_until (28 Feb), tapi patokannya tetap 31.
    'dari 28 Feb kembali ke 31' => ['2026-02-28', 1, 31, '2026-03-31'],
    'patokan 30 di bulan 31 hari' => ['2026-04-30', 1, 30, '2026-05-30'],
    'melewati pergantian tahun' => ['2026-12-31', 2, 31, '2027-02-28'],
    '12 bulan' => ['2026-03-15', 12, 15, '2027-03-15'],
]);
