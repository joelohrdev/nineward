<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Form;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory()->published(),
            'organization_id' => fn (array $attributes) => Form::withoutGlobalScopes()->whereKey($attributes['form_id'])->value('organization_id'),
            'form_version_id' => fn (array $attributes) => Form::withoutGlobalScopes()->whereKey($attributes['form_id'])->value('current_version_id'),
            'data' => [],
            'status' => SubmissionStatus::Processed,
            'review_reason' => null,
        ];
    }

    /**
     * Indicate that the submission is waiting for an admin to check it.
     */
    public function needsReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubmissionStatus::NeedsReview,
            'review_reason' => 'A player with the same name and birthday is already in your list.',
        ]);
    }
}
