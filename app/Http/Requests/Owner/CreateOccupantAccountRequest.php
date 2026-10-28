<?php

namespace App\Http\Requests\Owner;

use App\Models\Tenancy;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateOccupantAccountRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi penghuni langsung mendapat 403.
     */
    public function authorize(): bool
    {
        $tenancy = $this->route('tenancy');

        return $tenancy instanceof Tenancy && ($this->user()?->can('manageAccounts', $tenancy) ?? false);
    }

    /**
     * Username dan email dikecilkan sebelum cek unik (pola dari langkah 3b). Kalau baru
     * dikecilkan saat disimpan, "Eko" lolos cek unik lalu gagal di database dengan error 500.
     */
    protected function prepareForValidation(): void
    {
        foreach (['username', 'email'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => Str::lower($value)]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Huruf, angka, tanda hubung, dan garis bawah saja: mudah diketik di HP.
            'username' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash:ascii', Rule::unique(User::class)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique(User::class)],
        ];
    }
}
