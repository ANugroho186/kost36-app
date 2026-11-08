<?php

namespace App\Notifications;

/**
 * Untuk penghuni kamar itu: keluhan kamarnya sudah diselesaikan pemilik kost.
 */
class ComplaintResolved extends PortalNotification
{
    public function __construct(private string $title) {}

    protected function message(): string
    {
        return __('The complaint “:title” has been resolved.', ['title' => $this->title]);
    }

    protected function url(): string
    {
        return route('complaints.index', absolute: false);
    }
}
