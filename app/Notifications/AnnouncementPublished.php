<?php

namespace App\Notifications;

/**
 * Untuk penghuni kost itu: ada pengumuman yang mulai tayang. Hanya judulnya; isinya dibaca di aplikasi.
 */
class AnnouncementPublished extends PortalNotification
{
    public function __construct(private string $title) {}

    protected function message(): string
    {
        return __('New announcement: “:title”', ['title' => $this->title]);
    }

    protected function url(): string
    {
        return route('announcements.index', absolute: false);
    }
}
