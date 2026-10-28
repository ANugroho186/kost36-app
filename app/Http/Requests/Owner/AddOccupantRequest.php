<?php

namespace App\Http\Requests\Owner;

use App\Models\Tenancy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddOccupantRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi penghuni langsung mendapat 403.
     */
    public function authorize(): bool
    {
        $tenancy = $this->route('tenancy');

        return $tenancy instanceof Tenancy && ($this->user()?->can('addOccupant', $tenancy) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nickname' => ['required', 'string', 'max:50'],
            'moved_in_at' => ['required', 'date'],
        ];
    }
}
