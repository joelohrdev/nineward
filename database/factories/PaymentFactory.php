<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'organization_id' => fn (array $attributes) => Submission::withoutGlobalScopes()->whereKey($attributes['submission_id'])->value('organization_id'),
            'amount_cents' => 15000,
            'currency' => 'usd',
            'status' => PaymentStatus::Pending,
            'refunded_cents' => 0,
            'stripe_checkout_session_id' => 'cs_test_'.fake()->unique()->bothify('????????????'),
            'stripe_payment_intent_id' => null,
        ];
    }

    /**
     * Indicate that the family paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'stripe_payment_intent_id' => 'pi_test_'.fake()->unique()->bothify('????????????'),
            'paid_at' => now(),
        ]);
    }
}
