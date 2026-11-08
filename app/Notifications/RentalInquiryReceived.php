<?php

namespace App\Notifications;

/**
 * Untuk pemilik kost: ada permintaan survei baru dari halaman publik.
 */
class RentalInquiryReceived extends PortalNotification
{
    protected function message(): string
    {
        // Tanpa nama dan kontak calon penyewa: keduanya cukup ada di halaman permintaan survei,
        // yang ikut terhapus 30 hari setelah selesai.
        return __('New viewing request from the public page.');
    }

    protected function url(): string
    {
        return route('owner.rental-inquiries.index', absolute: false);
    }
}
