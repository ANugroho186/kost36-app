<?php

namespace App\Http\Requests\Owner;

use App\Models\Room;
use App\Models\Tenancy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartTenancyRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi penghuni langsung mendapat 403.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tenancy::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', Rule::exists(Room::class, 'id')],
            'start_date' => ['required', 'date'],
            // paid_until dihitung dari tanggal masuk + jumlah bulan yang dibayar di muka.
            'months_paid' => ['required', 'integer', 'between:1,12'],
            'nickname' => ['required', 'string', 'max:50'],
        ];
    }
}
