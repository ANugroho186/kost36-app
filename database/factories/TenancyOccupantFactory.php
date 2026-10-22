<?php

namespace Database\Factories;

use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenancyOccupant>
 */
class TenancyOccupantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenancy_id' => Tenancy::factory(),
            'user_id' => User::factory(),
            'nickname' => fake()->firstName(),
            'moved_in_at' => CarbonImmutable::today()->subDays(fake()->numberBetween(30, 180)),
        ];
    }

    /**
     * Indicate that the occupant has no login account.
     */
    public function withoutAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the occupant has moved out.
     */
    public function movedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'moved_out_at' => CarbonImmutable::today(),
        ]);
    }
}
