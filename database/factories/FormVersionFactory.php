<?php

namespace Database\Factories;

use App\Forms\FormSchema;
use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormVersion>
 */
class FormVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'version' => 1,
            'schema' => FormSchema::starter(),
            'published_at' => null,
        ];
    }
}
