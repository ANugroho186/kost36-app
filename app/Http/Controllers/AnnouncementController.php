<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengumuman dari sisi penghuni: hanya yang sedang tayang di kost tempat ia tinggal. Kost diambil
 * dari sewa yang sedang ditempati, jadi tidak ada {property} di URL yang bisa diganti.
 */
class AnnouncementController extends Controller
{
    public function index(#[CurrentUser] User $user): Response|RedirectResponse
    {
        // Pemilik melihat semua pengumuman, termasuk yang terjadwal, di halamannya sendiri.
        if ($user->isOwner()) {
            return to_route('owner.announcements.index');
        }

        $tenancy = $user->currentTenancy();

        return Inertia::render('tenant/announcements/index', [
            'announcements' => $tenancy === null ? [] : self::liveFor($tenancy),
        ]);
    }

    /**
     * Pengumuman yang sedang tayang untuk penghuni sewa itu, terbaru di atas. Dipakai juga oleh
     * dashboard penghuni (3 terbaru), supaya keduanya selalu menampilkan data yang sama.
     *
     * @return array<int, array{id: int, title: string, body: string, publishedAt: string}>
     */
    public static function liveFor(Tenancy $tenancy, ?int $limit = null): array
    {
        return $tenancy->room->property->announcements()
            ->live()
            ->latest('published_at')
            ->latest('id')
            ->when($limit, fn ($query, int $limit) => $query->limit($limit))
            ->get()
            ->map(fn (Announcement $announcement): array => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'publishedAt' => $announcement->published_at->format('Y-m-d\TH:i'),
            ])
            ->values()
            ->all();
    }
}
