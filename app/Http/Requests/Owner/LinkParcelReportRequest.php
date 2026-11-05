<?php

namespace App\Http\Requests\Owner;

use App\Models\Parcel;
use App\Models\ParcelReport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkParcelReportRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi penghuni (termasuk pelapornya) langsung mendapat 403.
     */
    public function authorize(): bool
    {
        $report = $this->route('parcelReport');

        return $report instanceof ParcelReport && ($this->user()?->can('link', $report) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Hanya paket yang belum diterima siapa pun: paket yang sudah sampai ke pemiliknya
            // tidak mungkin sedang dipegang pelapor.
            'parcel_id' => ['required', 'integer', Rule::exists(Parcel::class, 'id')->whereNull('collected_at')],
        ];
    }
}
