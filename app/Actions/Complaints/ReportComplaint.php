<?php

namespace App\Actions\Complaints;

use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use App\Support\ImageUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Mencatat keluhan baru beserta fotonya di disk privat.
 */
class ReportComplaint
{
    /**
     * Foto keluhan tidak pernah disajikan langsung oleh web server, hanya lewat route yang mengecek Policy.
     */
    public const DISK = 'local';

    public const MAX_PHOTOS = 4;

    public function __construct(private ImageUpload $images) {}

    /**
     * @param  array{title: string, description: string}  $details
     * @param  array<UploadedFile>  $photos
     * @param  User|null  $reporter  Null kalau pemilik mencatat atas nama penghuni tanpa akun.
     */
    public function handle(Room $room, ?User $reporter, array $details, array $photos): Complaint
    {
        $paths = [];

        try {
            // Foto diolah dulu (paling lama dan paling mungkin gagal), baru data disimpan.
            foreach ($photos as $photo) {
                $paths[] = $this->images->store($photo, 'complaints', self::DISK);
            }

            return DB::transaction(function () use ($room, $reporter, $details, $paths): Complaint {
                $complaint = new Complaint($details);
                $complaint->room()->associate($room);
                $complaint->reporter()->associate($reporter);
                $complaint->save();

                $complaint->photos()->createMany(array_map(fn (string $path) => ['path' => $path], $paths));

                return $complaint;
            });
        } catch (Throwable $e) {
            // Tanpa baris di database, file ini tidak bisa dilacak lagi dan tidak akan pernah ikut
            // terhapus. Foto kamar orang tidak boleh tertinggal di server.
            Storage::disk(self::DISK)->delete($paths);

            throw $e;
        }
    }
}
