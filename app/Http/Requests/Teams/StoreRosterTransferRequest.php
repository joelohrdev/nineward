<?php

namespace App\Http\Requests\Teams;

use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRosterTransferRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'team_id' => [
                'required',
                'integer',
                Rule::exists('teams', 'id')
                    ->where('organization_id', app(CurrentOrganization::class)->id())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'team_id.required' => 'Choose a team to move the player to.',
        ];
    }
}
