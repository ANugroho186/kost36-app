<?php

namespace Database\Factories;

use App\Enums\RentalInquiryStatus;
use App\Models\Property;
use App\Models\RentalInquiry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalInquiry>
 */
class RentalInquiryFactory extends Factory
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
            'name' => fake()->name(),
            // safeEmail memakai domain contoh (example.com dan sejenisnya), jadi tidak pernah menghubungi orang sungguhan.
            'contact' => fake()->safeEmail(),
            'preferred_date' => CarbonImmutable::today()->addDays(fake()->numberBetween(1, 14)),
            'message' => fake()->sentence(),
            'status' => RentalInquiryStatus::New,
        ];
    }

    /**
     * Indicate that the owner has finished handling the inquiry.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RentalInquiryStatus::Done,
            'done_at' => now(),
        ]);
    }
}
