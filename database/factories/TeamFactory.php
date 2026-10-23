<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Red', 'Blue', 'Gold', 'Black', 'White', 'Navy', 'Green', 'Orange', 'Silver', 'Maroon']);

        return [
            'season_id' => Season::factory(),
            'organization_id' => fn (array $attributes) => Season::withoutGlobalScopes()->whereKey($attributes['season_id'])->value('organization_id'),
            'division_id' => fn (array $attributes) => Division::factory()->create([
                'organization_id' => $attributes['organization_id'],
            ])->id,
            'head_coach_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
