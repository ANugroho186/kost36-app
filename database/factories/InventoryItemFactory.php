<?php

namespace Database\Factories;

use App\Enums\InventoryCondition;
use App\Models\InventoryItem;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
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
            'name' => fake()->randomElement(['Kasur', 'Lemari', 'Meja', 'Kipas angin', 'Kompor']),
            'condition' => InventoryCondition::Good,
        ];
    }

    /**
     * Indicate that the item is in a room, keeping the property consistent.
     */
    public function forRoom(Room $room): static
    {
        return $this->state(fn (array $attributes) => [
            'property_id' => $room->property_id,
            'room_id' => $room->id,
        ]);
    }

    /**
     * Indicate that the item needs repair.
     */
    public function needsRepair(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition' => InventoryCondition::NeedsRepair,
        ]);
    }
}
