<?php

namespace App\Forms;

use App\Models\FormVersion;
use Carbon\CarbonImmutable;

/**
 * Reads a validated submission's answers by what they mean rather than by
 * question ID, so processing doesn't depend on how a form was built.
 */
class SubmissionAnswers
{
    /**
     * @param  array<string, string|null>  $answers  keyed by field ID
     */
    public function __construct(private FormVersion $version, private array $answers) {}

    public function mapped(FieldMapping $mapping): ?string
    {
        foreach ($this->version->schema['fields'] as $field) {
            if (($field['maps_to'] ?? null) !== $mapping->value) {
                continue;
            }

            $answer = $this->answers[(string) $field['id']] ?? null;

            if ($answer === null || trim($answer) === '') {
                return null;
            }

            // Mapped dropdowns show labels ("Right"); records store values ("R").
            $options = $mapping->options();

            return $options === [] ? trim($answer) : (array_search($answer, $options, true) ?: null);
        }

        return null;
    }

    /**
     * Each question with the family's answer, in the order they were asked,
     * for showing a submission to an admin.
     *
     * @return list<array{label: string, answer: string|null, isSection: bool}>
     */
    public function readable(): array
    {
        return array_map(function (array $field): array {
            $isSection = $field['type'] === FieldType::Section->value;
            $answer = $isSection ? null : ($this->answers[(string) $field['id']] ?? null);

            return [
                'label' => (string) $field['label'],
                'answer' => match (true) {
                    $answer === null => null,
                    $field['type'] === FieldType::YesNo->value => ucfirst($answer),
                    $field['type'] === FieldType::Date->value => CarbonImmutable::parse($answer)->format('M j, Y'),
                    default => $answer,
                },
                'isSection' => $isSection,
            ];
        }, $this->version->schema['fields']);
    }
}
