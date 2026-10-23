import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import { DataTable } from '@/components/scorebook/data-table';
import { EmptyState } from '@/components/scorebook/empty-state';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { update as updateActiveSeason } from '@/routes/organization/active-season';
import { create as createRollover } from '@/routes/organization/season-rollover';
import { destroy, store, update } from '@/routes/organization/seasons';

type Season = {
    id: number;
    name: string;
    startsOn: string;
    endsOn: string;
    ageCutoffOn: string;
    isRegistrationOpen: boolean;
    isActive: boolean;
    hasEnded: boolean;
};

type Props = {
    seasonRows: Season[];
    suggestedAgeCutoffOn: string | null;
    teamCounts: Record<string, number>;
};

function SeasonFields({
    errors,
    season,
    suggestedAgeCutoffOn,
}: {
    errors: Record<string, string>;
    season?: Season;
    suggestedAgeCutoffOn?: string | null;
}) {
    const prefix = season ? `season-${season.id}` : 'new-season';

    return (
        <>
            <Field id={`${prefix}-name`} label="Name" error={errors.name}>
                <Input
                    id={`${prefix}-name`}
                    name="name"
                    defaultValue={season?.name}
                    placeholder="Spring 2026"
                    required
                />
            </Field>
            <div className="grid gap-5 sm:grid-cols-2">
                <Field
                    id={`${prefix}-starts`}
                    label="Starts"
                    error={errors.starts_on}
                >
                    <Input
                        id={`${prefix}-starts`}
                        name="starts_on"
                        type="date"
                        defaultValue={season?.startsOn}
                        required
                    />
                </Field>
                <Field
                    id={`${prefix}-ends`}
                    label="Ends"
                    error={errors.ends_on}
                >
                    <Input
                        id={`${prefix}-ends`}
                        name="ends_on"
                        type="date"
                        defaultValue={season?.endsOn}
                        required
                    />
                </Field>
            </div>
            <Field
                id={`${prefix}-cutoff`}
                label="Age cutoff date"
                hint="A player's league age is their age on this date."
                error={errors.age_cutoff_on}
            >
                <Input
                    id={`${prefix}-cutoff`}
                    name="age_cutoff_on"
                    type="date"
                    defaultValue={
                        season?.ageCutoffOn ?? suggestedAgeCutoffOn ?? undefined
                    }
                    required
                />
            </Field>
            <div className="flex items-center gap-3">
                <Checkbox
                    id={`${prefix}-registration`}
                    name="is_registration_open"
                    value="1"
                    defaultChecked={season?.isRegistrationOpen}
                />
                <Label htmlFor={`${prefix}-registration`}>
                    Registration is open
                </Label>
            </div>
        </>
    );
}

export default function Seasons({
    seasonRows: seasons,
    suggestedAgeCutoffOn,
    teamCounts,
}: Props) {
    return (
        <>
            <Head title="Seasons" />

            <section className="space-y-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div className="min-w-0 flex-1">
                        <SectionHeading
                            title="Seasons"
                            description="Teams and rosters belong to a season. Old seasons stay on file."
                        />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {seasons.length > 0 && (
                            <Button asChild variant="secondary">
                                <Link href={createRollover()}>
                                    Start next season
                                </Link>
                            </Button>
                        )}
                        <FormDialog
                            title="New season"
                            trigger={
                                <Button>
                                    <Plus />
                                    New season
                                </Button>
                            }
                            form={store.form()}
                            submitLabel="Create season"
                        >
                            {(errors) => (
                                <SeasonFields
                                    errors={errors}
                                    suggestedAgeCutoffOn={suggestedAgeCutoffOn}
                                />
                            )}
                        </FormDialog>
                    </div>
                </div>

                {seasons.length === 0 ? (
                    <EmptyState message="No seasons yet. Create one to start building teams." />
                ) : (
                    <DataTable
                        caption="Seasons"
                        rows={seasons}
                        rowKey={(season) => season.id}
                        columns={[
                            {
                                key: 'name',
                                header: 'Season',
                                primary: true,
                                cell: (season) => (
                                    <span className="inline-flex flex-wrap items-center gap-2">
                                        {season.name}
                                        {season.isActive && (
                                            <Badge>Active</Badge>
                                        )}
                                        {season.hasEnded && (
                                            <Badge variant="outline">
                                                Final
                                            </Badge>
                                        )}
                                    </span>
                                ),
                            },
                            {
                                key: 'dates',
                                header: 'Dates',
                                cell: (season) =>
                                    `${formatDate(season.startsOn)} – ${formatDate(season.endsOn)}`,
                            },
                            {
                                key: 'cutoff',
                                header: 'Age cutoff',
                                cell: (season) =>
                                    formatDate(season.ageCutoffOn),
                            },
                            {
                                key: 'registration',
                                header: 'Registration',
                                cell: (season) =>
                                    season.isRegistrationOpen ? (
                                        <StatusMark tone="done">
                                            Open
                                        </StatusMark>
                                    ) : (
                                        <StatusMark tone="neutral">
                                            Closed
                                        </StatusMark>
                                    ),
                            },
                            {
                                key: 'actions',
                                header: 'Actions',
                                className: 'md:text-right',
                                cell: (season) => (
                                    <span className="inline-flex flex-wrap gap-1 md:justify-end">
                                        {!season.isActive && (
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="ghost"
                                            >
                                                <Link
                                                    href={updateActiveSeason()}
                                                    data={{
                                                        season_id: season.id,
                                                    }}
                                                    as="button"
                                                    preserveScroll
                                                >
                                                    Make active
                                                </Link>
                                            </Button>
                                        )}
                                        <FormDialog
                                            title={`Edit ${season.name}`}
                                            trigger={
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                >
                                                    Edit
                                                </Button>
                                            }
                                            form={update.form(season.id)}
                                            submitLabel="Save season"
                                        >
                                            {(errors) => (
                                                <SeasonFields
                                                    errors={errors}
                                                    season={season}
                                                />
                                            )}
                                        </FormDialog>
                                        <ConfirmDialog
                                            title={`Delete ${season.name}?`}
                                            description={
                                                (teamCounts[season.id] ?? 0) > 0
                                                    ? `This also deletes its ${teamCounts[season.id]} teams and their rosters. Players stay in your master list.`
                                                    : 'Players stay in your master list.'
                                            }
                                            confirmLabel="Delete season"
                                            trigger={
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                >
                                                    Delete
                                                </Button>
                                            }
                                            form={destroy.form(season.id)}
                                        />
                                    </span>
                                ),
                            },
                        ]}
                    />
                )}
            </section>
        </>
    );
}
