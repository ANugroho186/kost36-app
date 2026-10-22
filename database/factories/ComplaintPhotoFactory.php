<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\ComplaintPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplaintPhoto>
 */
class ComplaintPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'path' => 'complaints/'.fake()->uuid().'.jpg',
        ];
    }
}
