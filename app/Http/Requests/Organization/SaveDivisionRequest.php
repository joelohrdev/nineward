<?php

namespace App\Http\Requests\Organization;

use App\Models\Division;
use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDivisionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $division = $this->route('division');

        return [
            'name' => [
                'required',
                'string',
                'max:40',
                Rule::unique('divisions')
                    ->where('organization_id', app(CurrentOrganization::class)->id())
                    ->ignore($division instanceof Division ? $division->id : null),
            ],
            'min_age' => ['nullable', 'integer', 'min:3', 'max:25'],
            'max_age' => ['nullable', 'integer', 'min:3', 'max:25', Rule::when($this->filled('min_age'), ['gte:min_age'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'You already have a division with that name.',
            'max_age.gte' => 'The oldest age has to be at least the youngest age.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'min_age' => 'youngest age',
            'max_age' => 'oldest age',
        ];
    }
}
