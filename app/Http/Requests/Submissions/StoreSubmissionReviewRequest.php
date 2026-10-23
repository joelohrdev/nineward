<?php

namespace App\Http\Requests\Submissions;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionReviewRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resolution' => ['required', 'in:same_player,different_player'],
        ];
    }

    public function isSamePlayer(): bool
    {
        return $this->input('resolution') === 'same_player';
    }
}
