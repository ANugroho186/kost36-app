<?php

namespace App\Http\Requests;

use App\Models\ParcelReport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Penghuni melaporkan paket orang lain yang sampai ke tangannya.
 */
class StoreParcelReportRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi pemilik kost dan penghuni yang sudah keluar langsung mendapat 403.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ParcelReport::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No. resi atau nama di label. Pelapor tidak memilih paket, karena ia tidak boleh
            // melihat daftar paket orang lain.
            'description' => ['required', 'string', 'max:500'],
        ];
    }
}
