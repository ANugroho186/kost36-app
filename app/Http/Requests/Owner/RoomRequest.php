<?php

namespace App\Http\Requests\Owner;

use App\Models\Property;
use App\Models\Room;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dipakai untuk menambah (tanpa {room} di route) maupun mengubah kamar.
 */
class RoomRequest extends FormRequest
{
    /**
     * Dicek sebelum validasi, jadi penghuni langsung mendapat 403.
     */
    public function authorize(): bool
    {
        $room = $this->room();

        return $room === null
            ? $this->user()?->can('create', Room::class) ?? false
            : $this->user()?->can('update', $room) ?? false;
    }

    /**
     * Checkbox yang tidak dicentang tidak ikut terkirim, jadi kosong berarti false.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_published' => $this->boolean('is_published')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $room = $this->room();
        // `??` bekerja seperti isset(): kalau $room null, langsung memakai kost pertama tanpa error.
        $propertyId = $room->property_id ?? Property::query()->oldest('id')->value('id');

        return [
            'number' => [
                'required',
                'string',
                'max:20',
                Rule::unique(Room::class)->where('property_id', $propertyId)->ignore($room),
            ],
            // Kost ini maksimal 2 orang per kamar.
            'capacity' => [
                'required',
                'integer',
                'between:1,2',
                function (string $attribute, mixed $value, Closure $fail) use ($room): void {
                    $occupants = $room?->activeOccupantCount() ?? 0;

                    if (is_numeric($value) && (int) $value < $occupants) {
                        $fail("This room has {$occupants} occupants right now, so the capacity cannot be lower.");
                    }
                },
            ],
            'facilities' => ['required', 'string', 'max:1000'],
            'is_published' => ['boolean'],
        ];
    }

    private function room(): ?Room
    {
        $room = $this->route('room');

        return $room instanceof Room ? $room : null;
    }
}
