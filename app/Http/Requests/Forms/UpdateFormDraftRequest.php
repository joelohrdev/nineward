<?php

namespace App\Http\Requests\Forms;

use App\Forms\FieldMapping;
use App\Forms\FieldType;
use App\Forms\FormSchema;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates a form schema from the builder. The builder is never trusted:
 * every type, mapping and option is checked here.
 */
class UpdateFormDraftRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fields' => ['required', 'array', 'max:'.FormSchema::MAX_FIELDS],
            'fields.*' => ['array'],
            'fields.*.id' => ['required', 'string', 'regex:/^f_[a-z0-9]{4,16}$/', 'distinct'],
            'fields.*.type' => ['required', Rule::enum(FieldType::class)],
            'fields.*.label' => ['required', 'string', 'max:160'],
            'fields.*.help' => ['nullable', 'string', 'max:300'],
            'fields.*.required' => ['boolean'],
            'fields.*.options' => ['nullable', 'array', 'max:50'],
            'fields.*.options.*' => ['required', 'string', 'max:80'],
            'fields.*.maps_to' => ['nullable', Rule::enum(FieldMapping::class)],
        ];
    }

    /**
     * Checks that span fields: every core question is present, a mapping is
     * used once, and custom dropdowns have distinct choices.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $fields = $this->array('fields');
                $mappings = array_values(array_filter(array_map(fn ($field): ?string => is_array($field) ? ($field['maps_to'] ?? null) : null, $fields)));

                foreach (FieldMapping::core() as $core) {
                    if (! in_array($core->value, $mappings, true)) {
                        $validator->errors()->add('fields', "Every registration form needs “{$core->defaultLabel()}”.");
                    }
                }

                foreach (array_count_values($mappings) as $mapping => $count) {
                    if ($count > 1) {
                        $validator->errors()->add('fields', 'Each player or guardian detail can only be asked once. “'.FieldMapping::from($mapping)->defaultLabel().'” appears more than once.');
                    }
                }

                foreach ($fields as $index => $field) {
                    if (! is_array($field) || ($field['type'] ?? null) !== FieldType::Select->value || ($field['maps_to'] ?? null) !== null) {
                        continue;
                    }

                    $options = array_map(fn ($option): string => mb_strtolower(trim((string) $option)), (array) ($field['options'] ?? []));

                    if (count($options) < 2) {
                        $validator->errors()->add("fields.{$index}.options", "“{$field['label']}” needs at least two choices.");
                    } elseif (count($options) !== count(array_unique($options))) {
                        $validator->errors()->add("fields.{$index}.options", "“{$field['label']}” lists the same choice twice.");
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fields.*.label.required' => 'Every question needs a label.',
            'fields.*.id.distinct' => 'Two questions have the same ID. Reload the builder and try again.',
            'fields.max' => 'A form can have up to '.FormSchema::MAX_FIELDS.' questions.',
        ];
    }

    /**
     * @return array{fields: list<array<string, mixed>>}
     */
    public function schema(): array
    {
        return FormSchema::normalize(array_values(array_filter($this->array('fields'), is_array(...))));
    }
}
