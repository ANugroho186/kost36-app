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
     * Aturan validasi untuk satu file foto, dipakai semua form unggah foto.
     *
     * `mimes` memeriksa isi file, bukan hanya akhiran nama file. `dimensions` mencegah gambar
     * raksasa berukuran file kecil yang bisa menghabiskan memori server saat diolah.
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'];
    }

    /**
     * Pesan error untuk rules(), misalnya messages('photos.*').
     *
     * Foto mode 50 MP (misalnya 8000 × 6000, sekitar 10 MB) sengaja ditolak karena boros memori
     * server. Pesannya memberi tahu apa yang harus dilakukan, bukan hanya batas angkanya.
     *
     * @return array<string, string>
     */
    public static function messages(string $attribute): array
    {
        $tooLarge = 'This photo is too large. Take it in your camera\'s normal mode instead of a high-resolution (50 MP) mode, or resize it first.';

        return [
            $attribute.'.max' => $tooLarge.' The limit is 5 MB per photo.',
            $attribute.'.dimensions' => $tooLarge.' The limit is 6000 pixels on each side.',
            $attribute.'.mimes' => 'Only JPG, PNG or WebP photos are allowed.',
        ];
    }

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
