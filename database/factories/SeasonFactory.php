<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) now()->format('Y');

        return [
            'organization_id' => Organization::factory(),
            // Years before 2000 so generated names never match the ones tests spell out.
            'name' => fake()->randomElement(['Spring', 'Summer', 'Fall']).' '.fake()->unique()->numberBetween(1900, 1999),
            'starts_on' => now()->startOfMonth(),
            'ends_on' => now()->startOfMonth()->addMonths(4),
            'age_cutoff_on' => "{$year}-05-01",
            'is_registration_open' => false,
        ];
    }

    /**
     * Indicate that the season is over.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_on' => now()->subMonths(8),
            'ends_on' => now()->subMonths(4),
        ]);
    }
}
