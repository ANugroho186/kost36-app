<?php

namespace Database\Factories;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'reporter_id' => User::factory(),
            'title' => fake()->randomElement(['Keran bocor', 'Lampu mati', 'Pintu macet', 'Kipas rusak']),
            'description' => fake()->sentence(),
            'status' => ComplaintStatus::Reported,
        ];
    }

    /**
     * Indicate that the owner is handling the complaint.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComplaintStatus::InProgress,
        ]);
    }

    /**
     * Indicate that the complaint has been resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComplaintStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }
}
