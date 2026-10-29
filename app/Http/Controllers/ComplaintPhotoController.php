<?php

namespace App\Http\Controllers;

use App\Actions\Complaints\ReportComplaint;
use App\Models\Complaint;
use App\Models\ComplaintPhoto;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Satu-satunya jalan untuk membuka foto keluhan dari disk privat. Dipakai halaman penghuni
 * maupun pemilik. Route-nya memakai scopeBindings(), jadi {photo} harus milik {complaint}.
 */
class ComplaintPhotoController extends Controller
{
    public function __invoke(Complaint $complaint, ComplaintPhoto $photo): StreamedResponse
    {
        // Siapa yang boleh melihat foto = siapa yang boleh melihat keluhannya.
        Gate::authorize('view', $complaint);

        $disk = Storage::disk(ReportComplaint::DISK);
        abort_unless($disk->exists($photo->path), 404);

        return $disk->response($photo->path, headers: [
            // Foto bagian dalam kamar orang: jangan disimpan di cache browser maupun proxy.
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
