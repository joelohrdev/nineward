<?php

namespace App\Http\Requests\Organization;

use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeasonRolloverRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'copy_head_coaches' => $this->boolean('copy_head_coaches'),
            'activate' => $this->boolean('activate'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = app(CurrentOrganization::class)->id();

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('seasons')->where('organization_id', $organizationId)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'age_cutoff_on' => ['required', 'date'],
            'source_season_id' => ['nullable', 'integer', Rule::exists('seasons', 'id')->where('organization_id', $organizationId)],
            'team_ids' => ['array', 'prohibited_if:source_season_id,null'],
            'team_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('teams', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('season_id', $this->integer('source_season_id'))
                    ->whereNull('deleted_at'),
            ],
            'copy_head_coaches' => ['boolean'],
            'activate' => ['boolean'],
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
            'team_ids.*.exists' => 'Teams can only be copied from the season you picked.',
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

    /**
     * @return array{name: string, starts_on: string, ends_on: string, age_cutoff_on: string}
     */
    public function seasonAttributes(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'starts_on' => $this->string('starts_on')->toString(),
            'ends_on' => $this->string('ends_on')->toString(),
            'age_cutoff_on' => $this->string('age_cutoff_on')->toString(),
        ];
    }

    /**
     * @return list<int>
     */
    public function teamIds(): array
    {
        return array_values(array_map(intval(...), $this->array('team_ids')));
    }
}
