<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Notifikasi portal milik user yang login, untuk pemilik maupun penghuni. Semua query lewat
 * $user->notifications(), jadi notifikasi milik orang lain tidak bisa dibuka lewat ID di URL (404).
 */
class NotificationController extends Controller
{
    private const LIMIT = 50;

    public function index(#[CurrentUser] User $user): Response
    {
        return Inertia::render('notifications/index', [
            'notifications' => $user->notifications()
                ->latest()
                ->limit(self::LIMIT)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => [
                    'id' => $notification->id,
                    'message' => self::stringFrom($notification, 'message'),
                    'createdAt' => $notification->created_at?->format('Y-m-d\TH:i'),
                    'read' => $notification->read_at !== null,
                ])
                ->all(),
        ]);
    }

    /**
     * Tandai sudah dibaca, lalu buka halaman yang dituju notifikasi itu. POST, bukan GET, supaya
     * prefetch tautan atau pratinjau link tidak ikut menandainya sudah dibaca.
     */
    public function open(#[CurrentUser] User $user, string $notification): RedirectResponse
    {
        $found = $user->notifications()->findOrFail($notification);
        $found->markAsRead();

        $url = self::stringFrom($found, 'url');

        // Hanya path di aplikasi ini. "//situs-lain" juga ditolak, karena browser membacanya sebagai alamat luar.
        return str_starts_with($url, '/') && ! str_starts_with($url, '//')
            ? redirect($url)
            : to_route('notifications.index');
    }

    public function readAll(#[CurrentUser] User $user): RedirectResponse
    {
        $user->unreadNotifications()->update(['read_at' => now()]);

        return to_route('notifications.index');
    }

    /**
     * Kolom `data` berupa JSON bebas. Nilai yang bukan teks dianggap kosong.
     */
    private static function stringFrom(DatabaseNotification $notification, string $key): string
    {
        $value = $notification->data[$key] ?? null;

        return is_string($value) ? $value : '';
    }
}
