<?php

namespace App\Http\Requests;

use App\Models\Tenancy;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateRoommateConsentRequest extends FormRequest
{
    /**
     * Penghuni kamar itu atau pemilik, hanya selama sewa aktif (lihat TenancyPolicy).
     */
    public function authorize(): bool
    {
        $tenancy = $this->tenancy();

        return $tenancy !== null && ($this->user()?->can('toggleRoommate', $tenancy) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'accepts_roommate' => ['required', 'boolean'],
        ];
    }

    /**
     * Kamar yang sudah penuh tidak perlu (dan tidak boleh) dibuka untuk orang berikutnya.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $tenancy = $this->tenancy();

                if ($tenancy !== null
                    && $this->boolean('accepts_roommate')
                    && $tenancy->activeOccupants()->count() >= $tenancy->room->capacity) {
                    $validator->errors()->add('accepts_roommate', __('This room is already full.'));
                }
            },
        ];
    }

    private function tenancy(): ?Tenancy
    {
        $tenancy = $this->route('tenancy');

        return $tenancy instanceof Tenancy ? $tenancy : null;
    }
}
