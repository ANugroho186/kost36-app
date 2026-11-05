<?php

namespace App\Enums;

/**
 * Status laporan paket salah terima, dihitung dari parcel_id dan resolved_at, tidak disimpan.
 */
enum ParcelReportStatus: string
{
    // Belum dicocokkan pemilik kost dengan paket mana pun.
    case Open = 'open';
    // Sudah ditautkan ke paket yang benar dan sedang diurus pemilik kost.
    case Linked = 'linked';
    case Resolved = 'resolved';
}
