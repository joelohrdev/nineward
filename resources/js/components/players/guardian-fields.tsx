import { Field } from '@/components/field';
import { Input } from '@/components/ui/input';

export type GuardianValues = {
    firstName: string;
    lastName: string;
    email: string;
    phone: string | null;
    relationship: string | null;
};

type Props = {
    errors: Record<string, string>;
    /** Prefix for input names, like "guardians[0]", or empty for top-level fields. */
    namePrefix?: string;
    /** Prefix for error keys, like "guardians.0.", matching Laravel's dot notation. */
    errorPrefix?: string;
    idPrefix: string;
    guardian?: GuardianValues;
    showRelationship?: boolean;
};

export function GuardianFields({
    errors,
    namePrefix = '',
    errorPrefix = '',
    idPrefix,
    guardian,
    showRelationship = true,
}: Props) {
    const name = (field: string) =>
        namePrefix === '' ? field : `${namePrefix}[${field}]`;
    const error = (field: string) => errors[`${errorPrefix}${field}`];

    return (
        <div className="grid gap-5">
            <div className="grid gap-5 sm:grid-cols-2">
                <Field
                    id={`${idPrefix}-first`}
                    label="First name"
                    error={error('first_name')}
                >
                    <Input
                        id={`${idPrefix}-first`}
                        name={name('first_name')}
                        defaultValue={guardian?.firstName}
                        autoComplete="off"
                        required
                    />
                </Field>
                <Field
                    id={`${idPrefix}-last`}
                    label="Last name"
                    error={error('last_name')}
                >
                    <Input
                        id={`${idPrefix}-last`}
                        name={name('last_name')}
                        defaultValue={guardian?.lastName}
                        autoComplete="off"
                        required
                    />
                </Field>
            </div>
            <div className="grid gap-5 sm:grid-cols-2">
                <Field
                    id={`${idPrefix}-email`}
                    label="Email"
                    error={error('email')}
                >
                    <Input
                        id={`${idPrefix}-email`}
                        name={name('email')}
                        type="email"
                        defaultValue={guardian?.email}
                        autoComplete="off"
                        required
                    />
                </Field>
                <Field
                    id={`${idPrefix}-phone`}
                    label="Phone"
                    error={error('phone')}
                >
                    <Input
                        id={`${idPrefix}-phone`}
                        name={name('phone')}
                        type="tel"
                        defaultValue={guardian?.phone ?? undefined}
                        autoComplete="off"
                    />
                </Field>
            </div>
            {showRelationship && (
                <Field
                    id={`${idPrefix}-relationship`}
                    label="Relationship"
                    hint="Optional, like Mother, Father or Grandparent."
                    error={error('relationship')}
                >
                    <Input
                        id={`${idPrefix}-relationship`}
                        name={name('relationship')}
                        defaultValue={guardian?.relationship ?? undefined}
                        autoComplete="off"
                    />
                </Field>
            )}
        </div>
    );
}
