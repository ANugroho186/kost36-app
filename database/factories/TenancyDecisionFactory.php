<?php

namespace Database\Factories;

use App\Enums\TenancyDecisionType;
use App\Models\Tenancy;
use App\Models\TenancyDecision;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenancyDecision>
 */
class TenancyDecisionFactory extends Factory
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
            'type' => TenancyDecisionType::Extend,
            'days' => 7,
            'new_date' => CarbonImmutable::today()->addDays(7),
        ];
    }
}
