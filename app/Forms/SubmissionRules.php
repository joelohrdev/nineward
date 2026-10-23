<?php

namespace App\Forms;

use App\Models\FormVersion;
use Illuminate\Validation\Rule;

/**
 * Turns a published form version into Laravel validation rules, so a
 * family's answers are checked against the exact questions they saw.
 */
class SubmissionRules
{
    /**
     * @return array<string, array<mixed>>
     */
    public static function for(FormVersion $version): array
    {
        $rules = [];

        foreach (self::answerFields($version) as $field) {
            $rules["answers.{$field['id']}"] = [
                $field['required'] ? 'required' : 'nullable',
                ...self::typeRules($field),
            ];
        }

        return $rules;
    }

    /**
     * Use each question's own wording in error messages.
     *
     * @return array<string, string>
     */
    public static function attributes(FormVersion $version): array
    {
        $attributes = [];

        foreach (self::answerFields($version) as $field) {
            $attributes["answers.{$field['id']}"] = mb_strtolower(rtrim((string) $field['label'], '?'));
        }

        return $attributes;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function answerFields(FormVersion $version): array
    {
        return array_values(array_filter(
            $version->schema['fields'],
            fn (array $field): bool => FieldType::from((string) $field['type'])->collectsAnswer(),
        ));
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<mixed>
     */
    private static function typeRules(array $field): array
    {
        $mapping = is_string($field['maps_to'] ?? null) ? FieldMapping::tryFrom($field['maps_to']) : null;

        if ($mapping === FieldMapping::PlayerDateOfBirth) {
            return ['date', 'before:today', 'after:'.now()->subYears(25)->toDateString()];
        }

        if ($mapping === FieldMapping::PlayerGraduationYear) {
            return ['integer', 'between:2000,2100'];
        }

        return match (FieldType::from((string) $field['type'])) {
            FieldType::ShortText => ['string', 'max:255'],
            FieldType::LongText => ['string', 'max:5000'],
            FieldType::Email => ['string', 'email', 'max:255'],
            FieldType::Phone => ['string', 'max:30', 'regex:/^[0-9 ()+.\-]{7,30}$/'],
            FieldType::Number => ['numeric', 'between:-1000000,1000000'],
            FieldType::Date => ['date'],
            FieldType::Select => [Rule::in((array) ($field['options'] ?? []))],
            FieldType::YesNo => [Rule::in(['yes', 'no'])],
            FieldType::Section => [],
        };
    }
}
