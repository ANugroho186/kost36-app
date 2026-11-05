<?php

namespace App\Http\Requests;

use App\Models\Parcel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Penghuni mendaftarkan atau mengubah paket miliknya sendiri.
 */
class ParcelRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi pemilik kost dan penghuni lain langsung mendapat 403.
     */
    public function authorize(): bool
    {
        $parcel = $this->route('parcel');

        return $parcel instanceof Parcel
            ? $this->user()?->can('update', $parcel) ?? false
            : $this->user()?->can('create', Parcel::class) ?? false;
    }

    /**
     * Resi dirapikan dulu (spasi dibuang, huruf kapital), supaya "jne 123" dan "JNE123" dianggap
     * resi yang sama dan pemilik mudah mencocokkannya dengan laporan salah terima.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('tracking_number'))) {
            $this->merge([
                'tracking_number' => strtoupper((string) preg_replace('/\s+/', '', $this->input('tracking_number'))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $parcel = $this->route('parcel');

        return [
            'tracking_number' => [
                'required',
                'string',
                'max:50',
                'alpha_dash:ascii',
                // Unik per penghuni saja. Resi yang sama milik penghuni lain tidak ditolak, karena
                // penolakan itu membocorkan bahwa resi tersebut terdaftar.
                Rule::unique(Parcel::class)
                    ->where('user_id', $this->user()?->id)
                    ->ignore($parcel instanceof Parcel ? $parcel->id : null),
            ],
            'item_name' => ['nullable', 'string', 'max:100'],
            'expected_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tracking_number.unique' => 'You have already registered this tracking number.',
            'tracking_number.alpha_dash' => 'Use only letters, numbers, dashes and underscores.',
        ];
    }
}
