<?php

namespace App\Http\Requests\Teams;

use App\Models\Season;
use App\Models\Team;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveTeamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = app(CurrentOrganization::class)->id();

        return [
            'name' => ['required', 'string', 'max:60'],
            'division_id' => ['required', 'integer', Rule::exists('divisions', 'id')->where('organization_id', $organizationId)],
            'head_coach_id' => ['nullable', 'integer', Rule::exists('organization_user', 'user_id')->where('organization_id', $organizationId)],
        ];
    }

    /**
     * Team names become their URL, so two teams in a season can't share one.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('name')) {
                    return;
                }

                $slug = Str::slug($this->string('name')->toString());

                if ($slug === '') {
                    $validator->errors()->add('name', 'Use at least one letter or number in the team name.');

                    return;
                }

                $season = $this->route('season');
                $team = $this->route('team');
                $teamId = $team instanceof Team ? $team->id : null;

                $isTaken = $season instanceof Season && Team::withTrashed()
                    ->whereBelongsTo($season)
                    ->where('slug', $slug)
                    ->when($teamId !== null, fn ($query) => $query->whereKeyNot($teamId))
                    ->exists();

                if ($isTaken) {
                    $validator->errors()->add('name', 'There is already a team with that name this season.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'division_id.required' => 'Choose a division.',
            'head_coach_id.exists' => 'The head coach has to be a member of your organization.',
        ];
    }

    /**
     * @return array{name: string, division_id: int, head_coach_id: int|null}
     */
    public function teamAttributes(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'division_id' => $this->integer('division_id'),
            'head_coach_id' => $this->filled('head_coach_id') ? $this->integer('head_coach_id') : null,
        ];
    }
}
