<?php

namespace Database\Factories;

use App\Models\MaintenanceLog;
use App\Models\Property;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceLog>
 */
class MaintenanceLogFactory extends Factory
{
    /**
     * Define the model's default state (area umum).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'room_id' => null,
            'performed_at' => CarbonImmutable::today()->subDays(fake()->numberBetween(0, 90)),
            'description' => fake()->randomElement(['Bersihkan saluran air', 'Ganti lampu', 'Cat ulang dinding']),
        ];
    }

    /**
     * Indicate that the maintenance was done in a room, keeping the property consistent.
     */
    public function forRoom(Room $room): static
    {
        return $this->state(fn (array $attributes) => [
            'property_id' => $room->property_id,
            'room_id' => $room->id,
        ]);
    }
}
