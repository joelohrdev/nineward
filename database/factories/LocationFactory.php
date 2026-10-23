<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
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
            'name' => fake()->lastName().' Park Field '.fake()->numberBetween(1, 4),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'maps_url' => null,
        ];
    }
}
