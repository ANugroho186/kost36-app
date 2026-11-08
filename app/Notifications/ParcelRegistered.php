<?php

namespace App\Notifications;

/**
 * Untuk pemilik kost: penghuni menunggu paket. Hanya no. resi, nickname, dan kamar, sama seperti
 * yang boleh dilihat pemilik kost. Nama barang tidak pernah dimasukkan ke notifikasi.
 */
class ParcelRegistered extends PortalNotification
{
    public function __construct(private string $recipient, private ?string $roomNumber, private string $trackingNumber) {}

    protected function message(): string
    {
        return $this->roomNumber === null
            ? __(':name is expecting parcel :tracking.', ['name' => $this->recipient, 'tracking' => $this->trackingNumber])
            : __(':name (room :room) is expecting parcel :tracking.', [
                'name' => $this->recipient,
                'room' => $this->roomNumber,
                'tracking' => $this->trackingNumber,
            ]);
    }

    protected function url(): string
    {
        return route('owner.parcels.index', absolute: false);
    }
}
