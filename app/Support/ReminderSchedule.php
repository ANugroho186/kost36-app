<?php

namespace App\Support;

use App\Enums\ReminderType;
use App\Enums\TenancyStatus;
use App\Models\Tenancy;
use Carbon\CarbonImmutable;

/**
 * Pengingat apa (kalau ada) yang jatuh pada hari tertentu untuk sebuah sewa. Fungsi murni:
 * tidak membaca database dan tidak mengirim apa pun, jadi mudah diuji per hari.
 *
 * Aturannya (docs/alur-bisnis.md, "Jadwal notifikasi"):
 * - Sewa aktif tanpa perpanjangan: H-3, H-0, H+1, H+4, H+7 dari paid_until, lalu H+10 ke pemilik.
 * - Sewa aktif dengan perpanjangan: penghuni diingatkan tepat pada extended_until, pemilik
 *   diminta memutuskan lagi sehari setelahnya. Tidak ada masa tenggang baru.
 * - Sewa nonaktif: pemilik diingatkan pada hari vacate_by.
 * - Pengingat yang terlewat (misalnya server mati pada jam itu) tidak dikirim belakangan.
 */
class ReminderSchedule
{
    /**
     * @return array{type: ReminderType, dueDate: CarbonImmutable}|null
     */
    public static function dueOn(Tenancy $tenancy, CarbonImmutable $day): ?array
    {
        $day = $day->startOfDay();

        if ($tenancy->status === TenancyStatus::Inactive) {
            return $tenancy->vacate_by?->isSameDay($day)
                ? ['type' => ReminderType::VacateDay, 'dueDate' => $tenancy->vacate_by]
                : null;
        }

        if ($tenancy->status !== TenancyStatus::Active) {
            return null;
        }

        $extendedUntil = $tenancy->extended_until;

        if ($extendedUntil !== null) {
            return match (true) {
                $extendedUntil->isSameDay($day) => ['type' => ReminderType::ExtensionLastDay, 'dueDate' => $extendedUntil],
                $extendedUntil->addDay()->isSameDay($day) => ['type' => ReminderType::ExtensionEnded, 'dueDate' => $extendedUntil],
                default => null,
            };
        }

        // Positif = sudah lewat jatuh tempo. Kedua tanggal tanpa jam, jadi hasilnya bilangan bulat.
        $daysPastDue = (int) $tenancy->paid_until->startOfDay()->diffInDays($day, false);

        foreach (ReminderType::cases() as $type) {
            if ($type->daysFromDueDate() === $daysPastDue) {
                return ['type' => $type, 'dueDate' => $tenancy->paid_until];
            }
        }

        return null;
    }
}
