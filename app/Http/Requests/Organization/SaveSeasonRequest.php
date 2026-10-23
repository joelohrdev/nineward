<?php

namespace App\Http\Requests\Organization;

use App\Models\Season;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSeasonRequest extends FormRequest
{
    /**
     * An unchecked checkbox sends nothing, which means registration is closed.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_registration_open' => $this->boolean('is_registration_open')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $season = $this->route('season');

        return [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('seasons')
                    ->where('organization_id', app(CurrentOrganization::class)->id())
                    ->ignore($season instanceof Season ? $season->id : null),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'age_cutoff_on' => ['required', 'date'],
            'is_registration_open' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'You already have a season with that name.',
            'ends_on.after_or_equal' => 'The season has to end on or after its start date.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'starts_on' => 'start date',
            'ends_on' => 'end date',
            'age_cutoff_on' => 'age cutoff date',
        ];
    }
}
