<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'title' => fake()->randomElement(['Jadwal kerja bakti', 'Pemadaman listrik', 'Perbaikan pompa air']),
            'body' => fake()->paragraph(),
            'published_at' => now(),
        ];
    }

    /**
     * Indicate that the announcement is no longer shown.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now()->subDays(14),
            'expires_at' => now()->subDays(7),
        ]);
    }
}
