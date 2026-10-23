<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PlatformAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformAuditLog>
 */
class PlatformAuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->platformAdmin(),
            'organization_id' => Organization::factory(),
            'action' => 'trial.extended',
            'reason' => fake()->sentence(),
            'metadata' => null,
        ];
    }
}
