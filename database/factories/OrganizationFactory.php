<?php

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Stripe\Subscription as StripeSubscription;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city().' '.fake()->randomElement(['Baseball', 'Travel Baseball', 'Baseball Club']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'owner_id' => User::factory(),
            'logo_path' => null,
            'primary_color' => null,
            'trial_ends_at' => now()->addDays(14),
        ];
    }

    /**
     * Indicate that the organization's free trial has ended without a subscription.
     */
    public function trialExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    /**
     * Give the organization an active subscription after its trial.
     */
    public function subscribed(): static
    {
        return $this->trialExpired()->afterCreating(function (Organization $organization): void {
            $organization->forceFill(['stripe_id' => 'cus_'.Str::random(14)])->save();
            $organization->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => 'sub_'.Str::random(14),
                'stripe_status' => StripeSubscription::STATUS_ACTIVE,
                'stripe_price' => 'price_test',
                'quantity' => 1,
            ]);
        });
    }

    /**
     * Every organization's owner is also a member with the Owner role.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Organization $organization): void {
            $organization->addMember($organization->owner, OrganizationRole::Owner);
        });
    }
}
