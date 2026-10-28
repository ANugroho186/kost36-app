<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Menyimpan gambar unggahan dengan cara membuatnya ulang dari piksel (GD), bukan menyalin file asli.
 *
 * - Metadata EXIF (koordinat GPS, model HP, waktu pemotretan) ikut hilang.
 * - Data tersembunyi yang mungkin disisipkan ke file gambar tidak ikut tersimpan.
 * - Gambar diputar sesuai orientasi EXIF dulu, supaya foto HP tidak tersimpan miring.
 * - Sisi terpanjang diperkecil menjadi MAX_SIDE dan disimpan sebagai WebP dengan nama acak.
 */
class ImageUpload
{
    public const MAX_SIDE = 1600;

    private const QUALITY = 82;

    /**
     * @return string Path relatif di disk, misalnya "rooms/abc....webp".
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $image = imagecreatefromstring($file->getContent());

        if ($image === false) {
            throw new RuntimeException('The uploaded file is not a readable image.');
        }

        $image = $this->scaleDown($this->applyOrientation($image, $file));
        // Supaya bagian transparan di PNG tetap transparan di WebP.
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, self::QUALITY);
        $contents = (string) ob_get_clean();

        $path = $directory.'/'.Str::random(40).'.webp';
        Storage::disk($disk)->put($path, $contents);

        return $path;
    }

    /**
     * Kamera HP sering menyimpan foto miring lalu menulis "putar 90°" di EXIF.
     * Karena EXIF dibuang, putarannya harus diterapkan langsung ke piksel.
     */
    private function applyOrientation(GdImage $image, UploadedFile $file): GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = is_array($exif) ? ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated === false ? $image : $rotated;
    }

    private function scaleDown(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) <= self::MAX_SIDE) {
            return $image;
        }

        $scaled = $width >= $height
            ? imagescale($image, self::MAX_SIDE)
            : imagescale($image, (int) round($width * self::MAX_SIDE / $height), self::MAX_SIDE);

        return $scaled === false ? $image : $scaled;
    }
}
