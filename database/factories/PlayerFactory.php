<?php

namespace Database\Factories;

use App\Enums\Handedness;
use App\Enums\Position;
use App\Enums\ThrowingArm;
use App\Models\Organization;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->dateTimeBetween('-14 years', '-8 years')->format('Y-m-d'),
            'graduation_year' => null,
            'bats' => fake()->randomElement(Handedness::cases()),
            'throws' => fake()->randomElement(ThrowingArm::cases()),
            'primary_position' => fake()->randomElement(Position::cases()),
            'notes' => null,
        ];
    }
}
