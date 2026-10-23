<?php

namespace App\Http\Requests\Organization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveLocationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'maps_url' => ['nullable', 'url:https', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'maps_url' => 'map link',
        ];
    }
}
