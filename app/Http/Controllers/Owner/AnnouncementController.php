<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AnnouncementRequest;
use App\Models\Announcement;
use App\Models\Property;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengumuman dari sisi pemilik, termasuk yang terjadwal dan kedaluwarsa. Buat, ubah, dan
 * hapus dibuka sebagai modal di halaman ini.
 */
class AnnouncementController extends Controller
{
    /**
     * Format input datetime-local. Dikirim sebagai jam dinding WIB tanpa zona waktu, jadi browser
     * tidak menggesernya.
     */
    private const DATETIME_LOCAL = 'Y-m-d\TH:i';

    public function index(): Response
    {
        Gate::authorize('manage', Announcement::class);

        return Inertia::render('owner/announcements/index', [
            'announcements' => Announcement::query()
                ->latest('published_at')
                ->latest('id')
                ->get()
                ->map(fn (Announcement $announcement): array => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'body' => $announcement->body,
                    'publishedAt' => $announcement->published_at->format(self::DATETIME_LOCAL),
                    'expiresAt' => $announcement->expires_at?->format(self::DATETIME_LOCAL),
                    'status' => $announcement->status->value,
                ])
                ->all(),
            // Pengumuman selalu milik kost yang profilnya sudah diisi.
            'canCreate' => Property::query()->exists(),
            // Nilai awal "tayang mulai" di form baru: sekarang, jadi pengumuman biasa langsung tayang.
            'now' => CarbonImmutable::now()->format(self::DATETIME_LOCAL),
        ]);
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        $property = Property::query()->oldest('id')->first();

        if ($property === null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Set up the property profile first.')]);

            return to_route('owner.property.edit');
        }

        $property->announcements()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement saved.')]);

        return to_route('owner.announcements.index');
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement updated.')]);

        return to_route('owner.announcements.index');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        Gate::authorize('delete', $announcement);

        $announcement->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement deleted.')]);

        return to_route('owner.announcements.index');
    }
}
