<?php

namespace App\Http\Requests;

use App\Actions\Complaints\ReportComplaint;
use App\Models\Complaint;
use App\Models\Room;
use App\Support\ImageUpload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Keluhan dari penghuni tentang kamarnya sendiri.
 */
class StoreComplaintRequest extends FormRequest
{
    private ?Room $room = null;

    /**
     * Kamar diambil dari sewa penghuni yang login, bukan dari input form, jadi tidak ada
     * room_id yang bisa diganti untuk melapor atas nama kamar lain. Dicek sebelum validasi.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $room = $user?->currentTenancy()?->room;

        if ($user === null || $room === null) {
            return false;
        }

        $this->room = $room;

        return $user->can('create', [Complaint::class, $room]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:'.ReportComplaint::MAX_PHOTOS],
            'photos.*' => ImageUpload::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.max' => 'You can attach at most '.ReportComplaint::MAX_PHOTOS.' photos.',
            ...ImageUpload::messages('photos.*'),
        ];
    }

    /**
     * Kamar penghuni yang melapor. Hanya bisa dipanggil setelah authorize() berhasil.
     */
    public function room(): Room
    {
        return $this->room ?? throw new LogicException('The complaint room is only known after authorization.');
    }
}
