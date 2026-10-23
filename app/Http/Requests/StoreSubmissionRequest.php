<?php

namespace App\Http\Requests;

use App\Forms\SubmissionRules;
use App\Models\Form;
use App\Models\FormVersion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A family's answers, validated against the version of the form they saw.
 */
class StoreSubmissionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $version = $this->formVersion();

        return [
            'answers' => ['array'],
            ...($version === null ? [] : SubmissionRules::for($version)),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $version = $this->formVersion();

        return $version === null ? [] : SubmissionRules::attributes($version);
    }

    /**
     * A field people can't see. Bots fill it in; people don't.
     */
    public function isFromBot(): bool
    {
        return $this->filled('website');
    }

    /**
     * Only the answers to questions on the form, keyed by question ID.
     *
     * @return array<string, string|null>
     */
    public function answers(): array
    {
        $version = $this->formVersion();
        $answers = [];

        if ($version === null) {
            return $answers;
        }

        foreach (SubmissionRules::answerFields($version) as $field) {
            $value = $this->input("answers.{$field['id']}");
            $answers[(string) $field['id']] = $value === null ? null : (string) $value;
        }

        return $answers;
    }

    private function formVersion(): ?FormVersion
    {
        $form = $this->route('form');

        return $form instanceof Form ? $form->currentVersion : null;
    }
}
