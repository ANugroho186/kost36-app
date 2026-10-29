<?php

namespace App\Http\Requests\Owner;

use App\Models\Room;
use App\Support\ImageUpload;
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
            'photos.*' => ImageUpload::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.max' => 'A room can have at most '.self::MAX_PHOTOS_PER_ROOM.' photos.',
            ...ImageUpload::messages('photos.*'),
        ];
    }
}
