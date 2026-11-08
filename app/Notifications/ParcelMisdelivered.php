<?php

namespace App\Notifications;

/**
 * Untuk penghuni pemilik paket: paketnya salah diterima orang lain dan sedang diurus pemilik kost.
 * Sengaja tanpa nama pelapor maupun isi laporannya.
 */
class ParcelMisdelivered extends PortalNotification
{
    public function __construct(private string $trackingNumber) {}

    protected function message(): string
    {
        return __('Parcel :tracking was received by someone else by mistake. The owner is sorting it out.', [
            'tracking' => $this->trackingNumber,
        ]);
    }

    protected function url(): string
    {
        return route('parcels.index', absolute: false);
    }
}
