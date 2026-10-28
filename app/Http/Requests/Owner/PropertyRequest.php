<?php

namespace App\Http\Requests\Owner;

use App\Models\Property;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PropertyRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi penghuni langsung mendapat 403.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage', Property::class) ?? false;
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
            'address' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:2000'],
            'rules' => ['required', 'string', 'max:2000'],
        ];
    }
}
