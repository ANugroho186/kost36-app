<?php

namespace App\Enums;

/**
 * Jenis pengingat harian. Satu sewa paling banyak menerima satu jenis per hari, dan setiap
 * pasangan (sewa, tanggal acuan, jenis) hanya dikirim sekali (unique di reminder_logs).
 */
enum ReminderType: string
{
    // Dihitung dari paid_until, untuk sewa aktif tanpa perpanjangan.
    case DueIn3Days = 'due_in_3_days';
    case DueToday = 'due_today';
    case GraceDay1 = 'grace_day_1';
    case GraceDay4 = 'grace_day_4';
    case GraceDay7 = 'grace_day_7';
    case DecisionNeeded = 'decision_needed';

    // Dihitung dari extended_until, untuk sewa aktif yang diperpanjang pemilik.
    case ExtensionLastDay = 'extension_last_day';
    case ExtensionEnded = 'extension_ended';

    // Dihitung dari vacate_by, untuk sewa nonaktif.
    case VacateDay = 'vacate_day';

    /**
     * Selisih hari dari paid_until (negatif = sebelum jatuh tempo) untuk pengingat yang
     * berpatokan pada paid_until. Null untuk jenis lain.
     */
    public function daysFromDueDate(): ?int
    {
        return match ($this) {
            self::DueIn3Days => -3,
            self::DueToday => 0,
            self::GraceDay1 => 1,
            self::GraceDay4 => 4,
            self::GraceDay7 => 7,
            self::DecisionNeeded => 10,
            default => null,
        };
    }

    /**
     * Pengingat yang meminta keputusan pemilik kost. Selebihnya untuk penghuni kamar itu.
     */
    public function isForOwner(): bool
    {
        return in_array($this, [self::DecisionNeeded, self::ExtensionEnded, self::VacateDay], true);
    }
}
