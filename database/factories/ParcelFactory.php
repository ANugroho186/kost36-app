<?php

namespace Database\Factories;

use App\Models\Parcel;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Parcel>
 */
class ParcelFactory extends Factory
{
    /**
     * Define the model's default state (menunggu).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tracking_number' => strtoupper(fake()->unique()->bothify('??##########')),
            'item_name' => fake()->randomElement(['Buku', 'Kaos', 'Charger HP', 'Sepatu']),
            'expected_date' => CarbonImmutable::today()->addDays(fake()->numberBetween(1, 5)),
        ];
    }

    /**
     * Indicate that the owner received the parcel and is holding it.
     */
    public function atOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'held_by_owner_at' => now(),
        ]);
    }

    /**
     * Indicate that the tenant picked the parcel up from the owner.
     */
    public function pickedUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'held_by_owner_at' => now()->subDay(),
            'collected_at' => now(),
        ]);
    }

    /**
     * Indicate that the tenant received the parcel from the courier directly.
     */
    public function receivedDirectly(): static
    {
        return $this->state(fn (array $attributes) => [
            'collected_at' => now(),
        ]);
    }
}
