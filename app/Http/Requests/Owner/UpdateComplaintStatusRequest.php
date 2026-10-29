<?php

namespace App\Http\Requests\Owner;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintStatusRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi penghuni langsung mendapat 403.
     */
    public function authorize(): bool
    {
        $complaint = $this->route('complaint');

        return $complaint instanceof Complaint && ($this->user()?->can('update', $complaint) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ComplaintStatus::class)],
            // Catatan perbaikan hanya berarti saat keluhan diselesaikan. Untuk status lain,
            // `exclude_unless` membuangnya dari data tervalidasi, jadi tidak tersimpan.
            'repair_description' => ['exclude_unless:status,'.ComplaintStatus::Resolved->value, 'nullable', 'string', 'max:2000'],
            'repaired_on' => ['exclude_unless:status,'.ComplaintStatus::Resolved->value, 'required_with:repair_description', 'nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
