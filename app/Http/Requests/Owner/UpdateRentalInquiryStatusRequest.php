<?php

namespace App\Http\Requests\Owner;

use App\Enums\RentalInquiryStatus;
use App\Models\RentalInquiry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRentalInquiryStatusRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi penghuni langsung mendapat 403.
     */
    public function authorize(): bool
    {
        $inquiry = $this->route('rental_inquiry');

        return $inquiry instanceof RentalInquiry && ($this->user()?->can('update', $inquiry) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(RentalInquiryStatus::class)],
        ];
    }
}
