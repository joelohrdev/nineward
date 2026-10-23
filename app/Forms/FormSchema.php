<?php

namespace App\Forms;

use Illuminate\Support\Str;

/**
 * Builds and normalizes form schemas.
 *
 * A schema is `{"fields": [field, ...]}` where each field is:
 *   id        stable key like "f_k2m9x1", never derived from the label
 *   type      a FieldType value
 *   label     the question
 *   help      optional hint under the question
 *   required  whether an answer is needed (always true for core fields)
 *   options   the choices, for dropdowns
 *   maps_to   optional FieldMapping value
 */
class FormSchema
{
    public const MAX_FIELDS = 60;

    /**
     * The schema every new form starts with: the core identity questions.
     *
     * @return array{fields: list<array<string, mixed>>}
     */
    public static function starter(): array
    {
        return ['fields' => array_map(fn (FieldMapping $mapping): array => self::mappedField($mapping), FieldMapping::core())];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mappedField(FieldMapping $mapping): array
    {
        return [
            'id' => self::newFieldId(),
            'type' => $mapping->type()->value,
            'label' => $mapping->defaultLabel(),
            'help' => null,
            'required' => $mapping->isCore(),
            'options' => array_values($mapping->options()),
            'maps_to' => $mapping->value,
        ];
    }

    public static function newFieldId(): string
    {
        return 'f_'.Str::lower(Str::random(8));
    }

    /**
     * Make a validated schema consistent: mapped fields take their type and
     * options from the mapping, core fields are always required, and keys
     * the builder doesn't own are dropped.
     *
     * @param  list<array<string, mixed>>  $fields
     * @return array{fields: list<array<string, mixed>>}
     */
    public static function normalize(array $fields): array
    {
        return ['fields' => array_map(function (array $field): array {
            $mapping = isset($field['maps_to']) && is_string($field['maps_to']) ? FieldMapping::tryFrom($field['maps_to']) : null;
            $type = $mapping?->type() ?? FieldType::from((string) $field['type']);

            return [
                'id' => (string) $field['id'],
                'type' => $type->value,
                'label' => trim((string) $field['label']),
                'help' => isset($field['help']) && trim((string) $field['help']) !== '' ? trim((string) $field['help']) : null,
                'required' => $type->collectsAnswer() && ($mapping?->isCore() || (bool) ($field['required'] ?? false)),
                'options' => match (true) {
                    $mapping !== null => array_values($mapping->options()),
                    $type->hasOptions() => array_values(array_map(fn ($option): string => trim((string) $option), (array) ($field['options'] ?? []))),
                    default => [],
                },
                'maps_to' => $mapping?->value,
            ];
        }, $fields)];
    }
}
