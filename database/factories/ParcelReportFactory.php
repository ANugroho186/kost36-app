<?php

namespace Database\Factories;

use App\Models\ParcelReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParcelReport>
 */
class ParcelReportFactory extends Factory
{
    /**
     * Define the model's default state (belum dicocokkan pemilik).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'description' => 'Saya menerima paket resi '.strtoupper(fake()->bothify('??##########')).' yang bukan milik saya.',
        ];
    }

    /**
     * Indicate that the owner has resolved the report.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => now(),
        ]);
    }
}
