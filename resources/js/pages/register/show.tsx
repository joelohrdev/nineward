import { Form, Head } from '@inertiajs/react';
import { FormFieldInput } from '@/components/forms/form-field-input';
import type { FormField } from '@/components/forms/schema';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import PublicLayout from '@/layouts/public-layout';
import { formatMoney } from '@/lib/format';
import type { PublicOrganization } from '@/layouts/public-layout';
import { store } from '@/routes/registration';

type Props = {
    organization: PublicOrganization;
    form: {
        title: string;
        slug: string;
        seasonName: string | null;
        isOpen: boolean;
        priceCents: number;
    };
    fields: FormField[];
};

export default function Register({ organization, form, fields }: Props) {
    return (
        <PublicLayout organization={organization}>
            <Head title={`${form.title} · ${organization.name}`} />

            <header className="mb-8 border-b-2 border-ink pb-4">
                <h1 className="font-display text-4xl leading-none uppercase md:text-5xl">
                    {form.title}
                </h1>
                {form.seasonName && (
                    <p className="mt-2 text-ink-muted">{form.seasonName}</p>
                )}
                {form.priceCents > 0 && (
                    <p className="mt-3 inline-flex items-baseline gap-2 border-2 border-ink px-3 py-1">
                        <span className="label-caps text-ink-muted">
                            Registration fee
                        </span>
                        <span className="font-display text-2xl">
                            {formatMoney(form.priceCents)}
                        </span>
                    </p>
                )}
            </header>

            {!form.isOpen ? (
                <p className="border-l-4 border-rule-strong bg-sheet px-4 py-3">
                    Registration is closed right now. Check with{' '}
                    {organization.name} about when it opens.
                </p>
            ) : (
                <Form
                    {...store.form({
                        organization: organization.slug,
                        form: form.slug,
                    })}
                    disableWhileProcessing
                    className="grid gap-6"
                >
                    {({ errors, processing, hasErrors }) => (
                        <>
                            {hasErrors && (
                                <p
                                    role="alert"
                                    className="border-l-4 border-danger bg-sheet px-3 py-2 font-medium text-danger"
                                >
                                    Some answers need another look. They're
                                    marked below.
                                </p>
                            )}
                            <p className="text-sm text-ink-muted">
                                Questions marked{' '}
                                <span className="text-danger">*</span> are
                                required.
                            </p>
                            {fields.map((field) => (
                                <FormFieldInput
                                    key={field.id}
                                    field={field}
                                    error={errors[`answers.${field.id}`]}
                                />
                            ))}
                            <div
                                aria-hidden="true"
                                className="absolute -left-[10000px] h-px w-px overflow-hidden"
                            >
                                <label>
                                    Leave this empty
                                    <input
                                        type="text"
                                        name="website"
                                        tabIndex={-1}
                                        autoComplete="off"
                                    />
                                </label>
                            </div>
                            <Button
                                type="submit"
                                variant="brand"
                                size="lg"
                                className="mt-2 w-full"
                            >
                                {processing && <Spinner />}
                                {form.priceCents > 0
                                    ? 'Continue to payment'
                                    : 'Submit registration'}
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </PublicLayout>
    );
}
