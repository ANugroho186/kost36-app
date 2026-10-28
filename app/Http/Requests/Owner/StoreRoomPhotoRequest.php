<?php

namespace App\Http\Requests\Owner;

use App\Models\Room;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoomPhotoRequest extends FormRequest
{
    public const MAX_PHOTOS_PER_ROOM = 8;

    /**
     * Dicek sebelum validasi, jadi penghuni langsung mendapat 403.
     */
    public function authorize(): bool
    {
        $room = $this->route('room');

        return $room instanceof Room && ($this->user()?->can('update', $room) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $room = $this->route('room');
        $remaining = self::MAX_PHOTOS_PER_ROOM - ($room instanceof Room ? $room->photos()->count() : 0);

        return [
            'photos' => ['required', 'array', 'min:1', 'max:'.max($remaining, 0)],
            // `mimes` memeriksa isi file, bukan hanya akhiran nama file. `dimensions` mencegah gambar
            // raksasa berukuran file kecil yang bisa menghabiskan memori server saat diolah.
            'photos.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        // Foto mode 50 MP (misalnya 8000 × 6000, sekitar 10 MB) sengaja ditolak karena boros memori
        // server. Pesannya memberi tahu apa yang harus dilakukan, bukan hanya batas angkanya.
        $tooLarge = 'This photo is too large. Take it in your camera\'s normal mode instead of a high-resolution (50 MP) mode, or resize it first.';

        return [
            'photos.max' => 'A room can have at most '.self::MAX_PHOTOS_PER_ROOM.' photos.',
            'photos.*.max' => $tooLarge.' The limit is 5 MB per photo.',
            'photos.*.dimensions' => $tooLarge.' The limit is 6000 pixels on each side.',
            'photos.*.mimes' => 'Only JPG, PNG or WebP photos are allowed.',
        ];
    }
}
