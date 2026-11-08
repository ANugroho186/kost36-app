<?php

namespace App\Notifications;

/**
 * Untuk pemilik kost: penghuni melaporkan keluhan baru.
 */
class ComplaintReported extends PortalNotification
{
    public function __construct(private string $roomNumber, private string $title) {}

    protected function message(): string
    {
        return __('New complaint for room :room: “:title”', ['room' => $this->roomNumber, 'title' => $this->title]);
    }

    protected function url(): string
    {
        return route('owner.complaints.index', absolute: false);
    }
}
