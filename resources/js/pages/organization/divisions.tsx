import { Head, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import { DataTable } from '@/components/scorebook/data-table';
import { EmptyState } from '@/components/scorebook/empty-state';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { update as updateOrder } from '@/routes/organization/division-order';
import { destroy, store, update } from '@/routes/organization/divisions';

type Division = {
    id: number;
    name: string;
    minAge: number | null;
    maxAge: number | null;
};

function ageRange(division: Division): string {
    if (division.minAge !== null && division.maxAge !== null) {
        return `${division.minAge}–${division.maxAge}`;
    }

    if (division.maxAge !== null) {
        return `${division.maxAge} and under`;
    }

    if (division.minAge !== null) {
        return `${division.minAge} and up`;
    }

    return 'Any age';
}

function DivisionFields({
    errors,
    division,
}: {
    errors: Record<string, string>;
    division?: Division;
}) {
    const prefix = division ? `division-${division.id}` : 'new-division';

    return (
        <>
            <Field id={`${prefix}-name`} label="Name" error={errors.name}>
                <Input
                    id={`${prefix}-name`}
                    name="name"
                    defaultValue={division?.name}
                    placeholder="10U"
                    required
                />
            </Field>
            <div className="grid gap-5 sm:grid-cols-2">
                <Field
                    id={`${prefix}-min`}
                    label="Youngest league age"
                    error={errors.min_age}
                >
                    <Input
                        id={`${prefix}-min`}
                        name="min_age"
                        type="number"
                        inputMode="numeric"
                        min={3}
                        max={25}
                        defaultValue={division?.minAge ?? undefined}
                    />
                </Field>
                <Field
                    id={`${prefix}-max`}
                    label="Oldest league age"
                    error={errors.max_age}
                >
                    <Input
                        id={`${prefix}-max`}
                        name="max_age"
                        type="number"
                        inputMode="numeric"
                        min={3}
                        max={25}
                        defaultValue={division?.maxAge ?? undefined}
                    />
                </Field>
            </div>
            <p className="text-sm text-ink-muted">
                Leave either age blank for no limit. For 10U, set the oldest age
                to 10.
            </p>
        </>
    );
}

export default function Divisions({ divisions }: { divisions: Division[] }) {
    const move = (index: number, offset: -1 | 1) => {
        const ids = divisions.map((division) => division.id);
        const [moved] = ids.splice(index, 1);
        ids.splice(index + offset, 0, moved);

        router.put(
            updateOrder.url(),
            { division_ids: ids },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Divisions" />

            <section className="space-y-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div className="min-w-0 flex-1">
                        <SectionHeading
                            title="Divisions"
                            description="Age groups used every season. Their order here is the order everywhere else."
                        />
                    </div>
                    <FormDialog
                        title="New division"
                        trigger={
                            <Button>
                                <Plus />
                                New division
                            </Button>
                        }
                        form={store.form()}
                        submitLabel="Create division"
                    >
                        {(errors) => <DivisionFields errors={errors} />}
                    </FormDialog>
                </div>

                {divisions.length === 0 ? (
                    <EmptyState message="No divisions yet. Add your age groups, like 9U, 10U and 11U." />
                ) : (
                    <DataTable
                        caption="Divisions"
                        numbered
                        rows={divisions}
                        rowKey={(division) => division.id}
                        columns={[
                            {
                                key: 'name',
                                header: 'Division',
                                primary: true,
                                cell: (division) => division.name,
                            },
                            {
                                key: 'ages',
                                header: 'League ages',
                                cell: ageRange,
                            },
                            {
                                key: 'actions',
                                header: 'Actions',
                                className: 'md:text-right',
                                cell: (division) => {
                                    const index = divisions.indexOf(division);

                                    return (
                                        <span className="inline-flex flex-wrap gap-1 md:justify-end">
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                disabled={index === 0}
                                                onClick={() => move(index, -1)}
                                                aria-label={`Move ${division.name} up`}
                                            >
                                                <ArrowUp />
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                disabled={
                                                    index ===
                                                    divisions.length - 1
                                                }
                                                onClick={() => move(index, 1)}
                                                aria-label={`Move ${division.name} down`}
                                            >
                                                <ArrowDown />
                                            </Button>
                                            <FormDialog
                                                title={`Edit ${division.name}`}
                                                trigger={
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                    >
                                                        Edit
                                                    </Button>
                                                }
                                                form={update.form(division.id)}
                                                submitLabel="Save division"
                                            >
                                                {(errors) => (
                                                    <DivisionFields
                                                        errors={errors}
                                                        division={division}
                                                    />
                                                )}
                                            </FormDialog>
                                            <ConfirmDialog
                                                title={`Delete ${division.name}?`}
                                                description="Teams can't use this division after it's deleted."
                                                confirmLabel="Delete division"
                                                trigger={
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                    >
                                                        Delete
                                                    </Button>
                                                }
                                                form={destroy.form(division.id)}
                                            />
                                        </span>
                                    );
                                },
                            },
                        ]}
                    />
                )}
            </section>
        </>
    );
}
