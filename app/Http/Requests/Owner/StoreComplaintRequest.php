<?php

namespace App\Http\Requests\Owner;

use App\Actions\Complaints\ReportComplaint;
use App\Models\Complaint;
use App\Models\Room;
use App\Support\ImageUpload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pemilik mencatat keluhan atas nama penghuni tanpa akun.
 */
class StoreComplaintRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi penghuni langsung mendapat 403. Hak atas kamar yang dipilih
     * dicek lagi lewat ComplaintPolicy::create di controller, setelah kamarnya pasti ada.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Complaint::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', Rule::exists(Room::class, 'id')],
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
}
