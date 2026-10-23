<?php

namespace App\Http\Requests\Players;

use App\Enums\Handedness;
use App\Enums\Position;
use App\Enums\ThrowingArm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePlayerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCreating = $this->route('player') === null;

        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'date_of_birth' => ['required', 'date', 'before:today', 'after:'.now()->subYears(25)->toDateString()],
            'graduation_year' => ['nullable', 'integer', 'between:2000,2100'],
            'bats' => ['nullable', Rule::enum(Handedness::class)],
            'throws' => ['nullable', Rule::enum(ThrowingArm::class)],
            'primary_position' => ['nullable', Rule::enum(Position::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            ...($isCreating ? $this->guardianRules() : []),
        ];
    }

    /**
     * New players are added with at least one guardian.
     *
     * @return array<string, array<mixed>>
     */
    private function guardianRules(): array
    {
        return [
            'guardians' => ['required', 'array', 'min:1', 'max:4'],
            'guardians.*.first_name' => ['required', 'string', 'max:60'],
            'guardians.*.last_name' => ['required', 'string', 'max:60'],
            'guardians.*.email' => ['required', 'string', 'email', 'max:255', 'distinct:ignore_case'],
            'guardians.*.phone' => ['nullable', 'string', 'max:30'],
            'guardians.*.relationship' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * The player's own fields, without guardians.
     *
     * @return array<string, mixed>
     */
    public function playerAttributes(): array
    {
        return $this->safe()->except('guardians');
    }

    /**
     * @return list<array{first_name: string, last_name: string, email: string, phone: string|null, relationship: string|null}>
     */
    public function guardians(): array
    {
        return array_values(array_map(fn (array $guardian): array => [
            'first_name' => (string) $guardian['first_name'],
            'last_name' => (string) $guardian['last_name'],
            'email' => (string) $guardian['email'],
            'phone' => isset($guardian['phone']) ? (string) $guardian['phone'] : null,
            'relationship' => isset($guardian['relationship']) ? (string) $guardian['relationship'] : null,
        ], $this->array('guardians')));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_of_birth.before' => 'The date of birth has to be in the past.',
            'date_of_birth.after' => 'Check the year: that player would be over 25.',
            'guardians.required' => 'Add at least one parent or guardian.',
            'guardians.*.email.distinct' => 'Each guardian needs their own email address.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'guardians.*.first_name' => 'guardian first name',
            'guardians.*.last_name' => 'guardian last name',
            'guardians.*.email' => 'guardian email',
            'guardians.*.phone' => 'guardian phone',
        ];
    }
}
