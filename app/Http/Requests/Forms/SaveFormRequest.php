<?php

namespace App\Http\Requests\Forms;

use App\Support\CurrentOrganization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:100'],
            'season_id' => ['nullable', 'integer', Rule::exists('seasons', 'id')->where('organization_id', app(CurrentOrganization::class)->id())],
            'price' => ['nullable', 'numeric', 'min:0', 'max:5000', 'decimal:0,2'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['price' => 'registration fee'];
    }

    public function priceCents(): int
    {
        return $this->filled('price') ? (int) round($this->float('price') * 100) : 0;
    }
}
