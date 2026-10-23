<?php

namespace App\Http\Requests\Teams;

use App\Enums\Position;
use App\Models\RosterEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRosterEntryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['is_captain' => $this->boolean('is_captain')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $entry = $this->route('rosterEntry');

        return [
            'jersey_number' => [
                'nullable',
                'integer',
                'between:0,99',
                Rule::unique('team_player', 'jersey_number')
                    ->where('team_id', $entry instanceof RosterEntry ? $entry->team_id : null)
                    ->ignore($entry instanceof RosterEntry ? $entry->id : null),
            ],
            'position' => ['nullable', Rule::enum(Position::class)],
            'is_captain' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jersey_number.unique' => 'Someone on this team already wears that number.',
            'jersey_number.between' => 'Jersey numbers run from 0 to 99.',
        ];
    }
}
