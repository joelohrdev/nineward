<?php

namespace App\Http\Requests\Organization;

use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActiveSeasonRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'season_id' => [
                'required',
                'integer',
                Rule::exists('seasons', 'id')->where('organization_id', app(CurrentOrganization::class)->id()),
            ],
        ];
    }
}
