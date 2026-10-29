<?php

namespace App\Enums;

/**
 * Status pengumuman dihitung dari published_at dan expires_at terhadap waktu sekarang, tidak disimpan.
 */
enum AnnouncementStatus: string
{
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Expired = 'expired';
}
