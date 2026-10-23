<?php

namespace Database\Factories;

use App\Enums\FormStatus;
use App\Forms\FormSchema;
use App\Models\Form;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Form>
 */
class FormFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->randomElement(['Spring', 'Summer', 'Fall']).' '.fake()->unique()->numberBetween(1900, 1999).' Registration';

        return [
            'organization_id' => Organization::factory(),
            'season_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'status' => FormStatus::Draft,
            'price_cents' => 0,
            'current_version_id' => null,
        ];
    }

    /**
     * Give the form a draft with the core questions, as new forms have.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Form $form): void {
            if (! $form->versions()->exists()) {
                $form->versions()->create(['version' => 1, 'schema' => FormSchema::starter()]);
            }
        });
    }

    /**
     * Publish the form's first version.
     */
    public function published(): static
    {
        return $this->afterCreating(function (Form $form): void {
            $version = $form->versions()->orderBy('version')->firstOrFail();
            $version->forceFill(['published_at' => now()])->save();
            $form->forceFill(['current_version_id' => $version->id, 'status' => FormStatus::Published])->save();
        });
    }
}
