import type { FormField } from '@/components/forms/schema';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';

const inputTypes: Partial<Record<FormField['type'], string>> = {
    short_text: 'text',
    email: 'email',
    phone: 'tel',
    number: 'number',
    date: 'date',
};

/**
 * One registration question, rendered the same way in the builder's preview
 * and on the public registration page.
 */
export function FormFieldInput({
    field,
    error,
    disabled = false,
}: {
    field: FormField;
    error?: string;
    disabled?: boolean;
}) {
    const id = `answer-${field.id}`;
    const name = `answers[${field.id}]`;

    if (field.type === 'section') {
        return (
            <div className="border-b-2 border-ink pt-4 pb-2">
                <h3 className="font-display text-2xl uppercase">
                    {field.label}
                </h3>
                {field.help && (
                    <p className="mt-1 text-ink-muted">{field.help}</p>
                )}
            </div>
        );
    }

    const label = (
        <Label htmlFor={id}>
            {field.label}
            {field.required && (
                <span className="text-danger" aria-hidden="true">
                    {' '}
                    *
                </span>
            )}
        </Label>
    );
    const help = field.help ? (
        <p id={`${id}-help`} className="text-sm text-ink-muted">
            {field.help}
        </p>
    ) : null;
    const describedBy = field.help ? `${id}-help` : undefined;

    if (field.type === 'yes_no') {
        return (
            <fieldset className="grid gap-2" aria-describedby={describedBy}>
                <legend className="mb-2 text-sm leading-none font-semibold text-ink">
                    {field.label}
                    {field.required && <span className="text-danger"> *</span>}
                </legend>
                {help}
                <div className="flex gap-6">
                    {['Yes', 'No'].map((choice) => (
                        <label
                            key={choice}
                            className="flex min-h-11 items-center gap-2"
                        >
                            <input
                                type="radio"
                                name={name}
                                value={choice.toLowerCase()}
                                required={field.required}
                                disabled={disabled}
                                className="size-5 accent-ink"
                            />
                            {choice}
                        </label>
                    ))}
                </div>
                <InputError message={error} />
            </fieldset>
        );
    }

    return (
        <div className="grid gap-2">
            {label}
            {field.type === 'long_text' ? (
                <textarea
                    id={id}
                    name={name}
                    rows={3}
                    required={field.required}
                    disabled={disabled}
                    aria-describedby={describedBy}
                    aria-invalid={error ? true : undefined}
                    className="w-full rounded-sm border border-b-2 border-rule border-b-rule-strong bg-sheet px-3 py-2 text-base text-ink outline-none focus-visible:border-b-clay focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-clay"
                />
            ) : field.type === 'select' ? (
                <NativeSelect
                    id={id}
                    name={name}
                    required={field.required}
                    disabled={disabled}
                    defaultValue=""
                    aria-describedby={describedBy}
                    aria-invalid={error ? true : undefined}
                >
                    <option value="" disabled={field.required}>
                        Choose one
                    </option>
                    {field.options.map((option) => (
                        <option key={option} value={option}>
                            {option}
                        </option>
                    ))}
                </NativeSelect>
            ) : (
                <Input
                    id={id}
                    name={name}
                    type={inputTypes[field.type] ?? 'text'}
                    required={field.required}
                    disabled={disabled}
                    aria-describedby={describedBy}
                    aria-invalid={error ? true : undefined}
                    autoComplete="off"
                />
            )}
            {help}
            <InputError message={error} />
        </div>
    );
}
