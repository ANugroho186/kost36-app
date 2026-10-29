<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Complaints\ChangeComplaintStatus;
use App\Actions\Complaints\ReportComplaint;
use App\Enums\ComplaintStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreComplaintRequest;
use App\Http\Requests\Owner\UpdateComplaintStatusRequest;
use App\Models\Complaint;
use App\Models\ComplaintPhoto;
use App\Models\MaintenanceLog;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Keluhan semua kamar dari sisi pemilik. Mencatat, menyelesaikan, dan menghapus dibuka sebagai modal.
 */
class ComplaintController extends Controller
{
    private const FILTERS = ['open', 'resolved', 'all'];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Complaint::class);

        // Nilai lain dari URL dianggap "open", bukan error.
        $filter = $request->string('status')->value();
        $filter = in_array($filter, self::FILTERS, true) ? $filter : 'open';

        $complaints = Complaint::query()
            ->with(['room', 'reporter', 'photos', 'maintenanceLogs'])
            ->when($filter === 'open', fn (Builder $query) => $query->where('status', '!=', ComplaintStatus::Resolved))
            ->when($filter === 'resolved', fn (Builder $query) => $query->where('status', ComplaintStatus::Resolved))
            ->latest()
            ->latest('id')
            ->get()
            ->map(fn (Complaint $complaint): array => [
                'id' => $complaint->id,
                'roomNumber' => $complaint->room->number,
                'title' => $complaint->title,
                'description' => $complaint->description,
                'status' => $complaint->status->value,
                'reportedAt' => $complaint->created_at?->toDateString(),
                'resolvedAt' => $complaint->resolved_at?->toDateString(),
                // Nama akun pelapor. Null kalau dicatat pemilik atau akunnya sudah dihapus.
                'reporter' => $complaint->reporter?->name,
                'photos' => $complaint->photos->map(fn (ComplaintPhoto $photo): array => [
                    'id' => $photo->id,
                    'url' => route('complaints.photos.show', [$complaint, $photo]),
                ])->all(),
                'repairs' => $complaint->maintenanceLogs->map(fn (MaintenanceLog $log): array => [
                    'id' => $log->id,
                    'performedAt' => $log->performed_at->toDateString(),
                    'description' => $log->description,
                ])->all(),
            ])
            ->all();

        return Inertia::render('owner/complaints/index', [
            'complaints' => $complaints,
            'filter' => $filter,
            'counts' => [
                'open' => Complaint::query()->where('status', '!=', ComplaintStatus::Resolved)->count(),
                'resolved' => Complaint::query()->where('status', ComplaintStatus::Resolved)->count(),
            ],
            // Pilihan kamar untuk modal "Record a complaint".
            'rooms' => Room::query()
                ->orderByRaw('length(number), number')
                ->get()
                ->map(fn (Room $room): array => ['id' => $room->id, 'number' => $room->number])
                ->all(),
            'maxPhotos' => ReportComplaint::MAX_PHOTOS,
            'today' => CarbonImmutable::today()->toDateString(),
        ]);
    }

    /**
     * Keluhan dari penghuni tanpa akun. Pelapornya kosong, karena yang mengalami bukan pemilik.
     */
    public function store(StoreComplaintRequest $request, ReportComplaint $report): RedirectResponse
    {
        $room = Room::query()->findOrFail($request->integer('room_id'));
        Gate::authorize('create', [Complaint::class, $room]);

        $report->handle(
            $room,
            null,
            ['title' => $request->string('title')->value(), 'description' => $request->string('description')->value()],
            Arr::wrap($request->file('photos')),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Complaint recorded.')]);

        return to_route('owner.complaints.index');
    }

    public function update(UpdateComplaintStatusRequest $request, Complaint $complaint, ChangeComplaintStatus $change): RedirectResponse
    {
        // Hanya data tervalidasi: catatan perbaikan sudah dibuang kalau statusnya bukan "resolved".
        $validated = $request->safe();
        $status = ComplaintStatus::from($validated->string('status')->value());
        $repair = $validated->filled('repair_description')
            ? [
                'performed_at' => CarbonImmutable::parse($validated->string('repaired_on')->value()),
                'description' => $validated->string('repair_description')->value(),
            ]
            : null;

        $change->handle($complaint, $status, $repair);

        Inertia::flash('toast', ['type' => 'success', 'message' => match ($status) {
            ComplaintStatus::Reported => __('Complaint marked as reported.'),
            ComplaintStatus::InProgress => __('Complaint marked as in progress.'),
            ComplaintStatus::Resolved => __('Complaint resolved.'),
        }]);

        return back(fallback: route('owner.complaints.index'));
    }

    /**
     * Misalnya keluhan ganda atau salah catat. Catatan perawatannya tetap ada (complaint_id menjadi null).
     */
    public function destroy(Complaint $complaint): RedirectResponse
    {
        Gate::authorize('delete', $complaint);

        // Cascade hanya menghapus baris foto di database. File di disk privat dihapus di sini.
        Storage::disk(ReportComplaint::DISK)->delete($complaint->photos->pluck('path')->all());
        $complaint->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Complaint deleted.')]);

        return back(fallback: route('owner.complaints.index'));
    }
}
