import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import { DataTable } from '@/components/scorebook/data-table';
import { EmptyState } from '@/components/scorebook/empty-state';
import { PageHeader } from '@/components/scorebook/page-header';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { edit, store } from '@/routes/forms';
import { index as submissionsIndex } from '@/routes/submissions';

type FormRow = {
    id: number;
    title: string;
    status: 'draft' | 'published' | 'closed';
    statusLabel: string;
    seasonName: string | null;
    publishedVersion: number | null;
    hasUnpublishedChanges: boolean;
};

const tones = { draft: 'open', published: 'done', closed: 'neutral' } as const;

export default function Forms({
    forms,
    seasonOptions,
}: {
    forms: FormRow[];
    seasonOptions: { id: number; name: string }[];
}) {
    const newForm = (
        <FormDialog
            title="New registration form"
            description="It starts with the questions every registration needs. Add your own after."
            trigger={
                <Button>
                    <Plus />
                    New form
                </Button>
            }
            form={store.form()}
            submitLabel="Create and edit"
        >
            {(errors) => (
                <>
                    <Field
                        id="new-form-title"
                        label="Title"
                        error={errors.title}
                    >
                        <Input
                            id="new-form-title"
                            name="title"
                            placeholder="Spring 2027 tryouts"
                            required
                        />
                    </Field>
                    <Field
                        id="new-form-season"
                        label="Season"
                        error={errors.season_id}
                    >
                        <NativeSelect
                            id="new-form-season"
                            name="season_id"
                            defaultValue={seasonOptions[0]?.id ?? ''}
                        >
                            <option value="">No particular season</option>
                            {seasonOptions.map((season) => (
                                <option key={season.id} value={season.id}>
                                    {season.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                </>
            )}
        </FormDialog>
    );

    return (
        <>
            <Head title="Registration" />

            <PageHeader
                title="Registration"
                context="Forms families fill in to sign a player up"
                actions={
                    <>
                        <Button asChild variant="outline">
                            <Link href={submissionsIndex()}>
                                Registrations inbox
                            </Link>
                        </Button>
                        {newForm}
                    </>
                }
            />

            {forms.length === 0 ? (
                <EmptyState
                    message="No registration forms yet."
                    action={newForm}
                />
            ) : (
                <DataTable
                    caption="Registration forms"
                    rows={forms}
                    rowKey={(form) => form.id}
                    columns={[
                        {
                            key: 'title',
                            header: 'Form',
                            primary: true,
                            cell: (form) => (
                                <Link
                                    href={edit(form.id)}
                                    className="underline decoration-rule-strong underline-offset-4 hover:decoration-clay"
                                >
                                    {form.title}
                                </Link>
                            ),
                        },
                        {
                            key: 'season',
                            header: 'Season',
                            cell: (form) => form.seasonName ?? '–',
                        },
                        {
                            key: 'status',
                            header: 'Status',
                            cell: (form) => (
                                <span className="inline-flex flex-wrap items-center gap-2">
                                    <StatusMark tone={tones[form.status]}>
                                        {form.statusLabel}
                                    </StatusMark>
                                    {form.hasUnpublishedChanges && (
                                        <span className="text-sm text-ink-muted">
                                            Unpublished changes
                                        </span>
                                    )}
                                </span>
                            ),
                        },
                        {
                            key: 'version',
                            header: 'Version',
                            cell: (form) => form.publishedVersion ?? '–',
                        },
                    ]}
                />
            )}
        </>
    );
}
