<?php

namespace App\Notifications;

/**
 * Untuk penghuni pemilik paket: paketnya sudah diterima pemilik kost.
 *
 * Hanya no. resi. Nama barang tersimpan terenkripsi di tabel parcels, dan akan tersimpan sebagai
 * teks biasa kalau ikut ditulis ke tabel notifications.
 */
class ParcelHeldByOwner extends PortalNotification
{
    public function __construct(private string $trackingNumber) {}

    protected function message(): string
    {
        return __('Parcel :tracking is at the owner\'s place. You can pick it up.', ['tracking' => $this->trackingNumber]);
    }

    protected function url(): string
    {
        return route('parcels.index', absolute: false);
    }
}
