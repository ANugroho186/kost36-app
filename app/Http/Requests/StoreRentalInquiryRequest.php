<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRentalInquiryRequest extends FormRequest
{
    /**
     * Form publik: siapa pun boleh mengirim, pembatasnya adalah rate limit di route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // Nomor WhatsApp atau email, ditulis bebas oleh calon penyewa.
            'contact' => ['required', 'string', 'max:100'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
