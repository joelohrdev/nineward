<?php

namespace App\Http\Requests\Platform;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExtendTrialRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'days' => ['required', 'integer', 'min:1', 'max:90'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
