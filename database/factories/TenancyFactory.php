<?php

namespace Database\Factories;

use App\Enums\TenancyStatus;
use App\Models\Room;
use App\Models\Tenancy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenancy>
 */
class TenancyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $today = CarbonImmutable::today();

        return [
            'room_id' => Room::factory(),
            'start_date' => $today->subDays(fake()->numberBetween(30, 180)),
            // Tanggal bayar berikutnya, masih di depan (sewa lancar).
            'paid_until' => $today->addDays(fake()->numberBetween(1, 28)),
            'status' => TenancyStatus::Active,
        ];
    }

    /**
     * Indicate that the owner has deactivated the tenancy.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenancyStatus::Inactive,
            'deactivated_at' => now(),
            'vacate_by' => CarbonImmutable::today()->addDays(7),
        ]);
    }

    /**
     * Indicate that everyone has moved out of the room.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenancyStatus::Ended,
            'end_date' => CarbonImmutable::today(),
        ]);
    }
}
