<?php

namespace App\Notifications;

/**
 * Untuk pemilik kost: ada penghuni yang menerima paket bukan miliknya.
 */
class ParcelReportReceived extends PortalNotification
{
    protected function message(): string
    {
        return __('A tenant received a parcel that is not theirs.');
    }

    protected function url(): string
    {
        return route('owner.parcels.index', absolute: false);
    }
}
