<?php

namespace App\Http\Requests\Owner;

use App\Models\TenancyOccupant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetOccupantPasswordRequest extends FormRequest
{
    /**
     * Pemilik saja, dan hanya untuk akun penghuni (lihat UserPolicy::resetPassword).
     */
    public function authorize(): bool
    {
        $occupant = $this->route('occupant');
        $account = $occupant instanceof TenancyOccupant ? $occupant->user : null;

        return $account !== null && ($this->user()?->can('resetPassword', $account) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Reset bisa dipakai untuk mengambil alih akun penghuni. Meminta password pemilik
            // mencegahnya dilakukan oleh orang lain yang kebetulan memakai laptop pemilik.
            'owner_password' => ['required', 'string', 'current_password'],
        ];
    }
}
