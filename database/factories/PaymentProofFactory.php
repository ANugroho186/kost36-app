<?php

namespace Database\Factories;

use App\Enums\PaymentProofStatus;
use App\Models\PaymentProof;
use App\Models\Tenancy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentProof>
 */
class PaymentProofFactory extends Factory
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
            'uploaded_by' => User::factory(),
            'file_path' => 'payment-proofs/'.fake()->uuid().'.jpg',
            'status' => PaymentProofStatus::Pending,
        ];
    }

    /**
     * Indicate that the owner approved the proof for one month.
     */
    public function approved(): static
    {
        $before = CarbonImmutable::today();

        return $this->state(fn (array $attributes) => [
            'status' => PaymentProofStatus::Approved,
            'months_granted' => 1,
            'paid_until_before' => $before,
            'paid_until_after' => $before->addMonthsNoOverflow(1),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Indicate that the owner rejected the proof.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentProofStatus::Rejected,
            'review_note' => 'Transfer tidak ditemukan di mutasi rekening.',
            'reviewed_at' => now(),
        ]);
    }
}
