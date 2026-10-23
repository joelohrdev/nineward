<?php

namespace App\Http\Requests\Teams;

use App\Models\RosterEntry;
use App\Models\Team;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRosterEntriesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'player_ids' => ['required', 'array', 'min:1', 'max:100'],
            'player_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('players', 'id')
                    ->where('organization_id', app(CurrentOrganization::class)->id())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * A player can only be on one team per season.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $team = $this->route('team');

                if ($validator->errors()->isNotEmpty() || ! $team instanceof Team) {
                    return;
                }

                $alreadyRostered = RosterEntry::query()
                    ->where('season_id', $team->season_id)
                    ->whereIn('player_id', $this->playerIds())
                    ->with(['player:id,first_name,last_name', 'team:id,name'])
                    ->get();

                foreach ($alreadyRostered as $entry) {
                    $validator->errors()->add('player_ids', "{$entry->player->fullName()} is already on {$entry->team->name} this season.");
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
            'player_ids.required' => 'Select at least one player.',
            'player_ids.*.exists' => 'One of those players is no longer in your list. Refresh and try again.',
        ];
    }

    /**
     * @return list<int>
     */
    public function playerIds(): array
    {
        return array_values(array_map(intval(...), $this->array('player_ids')));
    }
}
