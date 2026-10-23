<?php

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationInvitation>
 */
class OrganizationInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => OrganizationRole::Coach,
            'invited_by' => User::factory(),
            'expires_at' => now()->addDays(OrganizationInvitation::VALID_FOR_DAYS),
            'accepted_at' => null,
        ];
    }

    /**
     * Indicate that the invitation has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
