<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $maxAge = fake()->unique()->numberBetween(8, 18);

        return [
            'organization_id' => Organization::factory(),
            // A color suffix keeps generated names from matching ones tests spell out, like "10U".
            'name' => "{$maxAge}U ".fake()->unique()->safeColorName(),
            'min_age' => null,
            'max_age' => $maxAge,
            'display_order' => 0,
        ];
    }
}
