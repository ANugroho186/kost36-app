<?php

namespace Database\Factories;

use App\Enums\ReminderType;
use App\Models\ReminderLog;
use App\Models\Tenancy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReminderLog>
 */
class ReminderLogFactory extends Factory
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
            'due_date' => CarbonImmutable::today()->addDays(3),
            'type' => ReminderType::DueIn3Days,
            'sent_at' => now(),
        ];
    }
}
