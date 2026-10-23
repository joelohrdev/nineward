import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { useState } from 'react';
import { Field } from '@/components/field';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { store } from '@/routes/organization/season-rollover';
import { index as seasonsIndex } from '@/routes/organization/seasons';

type SourceSeason = {
    id: number;
    name: string;
    teams: {
        id: number;
        name: string;
        divisionName: string;
        headCoachName: string | null;
    }[];
};

type Preview = {
    returningCount: number;
    movingUp: { name: string; from: string | null; to: string | null }[];
};

type Props = {
    sourceSeasons: SourceSeason[];
    defaults: {
        sourceSeasonId: number | null;
        startsOn: string | null;
        endsOn: string | null;
        ageCutoffOn: string | null;
    };
    preview?: Preview | null;
};

const STEPS = ['Details', 'Teams', 'Review'] as const;

export default function SeasonRollover({
    sourceSeasons: seasons,
    defaults,
    preview,
}: Props) {
    const [step, setStep] = useState(0);
    const [isPreviewing, setIsPreviewing] = useState(false);
    const firstSource = seasons.find(
        (season) => season.id === defaults.sourceSeasonId,
    );

    const form = useForm({
        name: '',
        starts_on: defaults.startsOn ?? '',
        ends_on: defaults.endsOn ?? '',
        age_cutoff_on: defaults.ageCutoffOn ?? '',
        source_season_id: defaults.sourceSeasonId,
        team_ids: firstSource?.teams.map((team) => team.id) ?? [],
        copy_head_coaches: false,
        activate: true,
    });

    const source = seasons.find(
        (season) => season.id === form.data.source_season_id,
    );

    const chooseSource = (value: string) => {
        const next = seasons.find((season) => season.id === Number(value));
        form.setData((data) => ({
            ...data,
            source_season_id: next?.id ?? null,
            team_ids: next?.teams.map((team) => team.id) ?? [],
        }));
    };

    const toggleTeam = (teamId: number) =>
        form.setData(
            'team_ids',
            form.data.team_ids.includes(teamId)
                ? form.data.team_ids.filter((id) => id !== teamId)
                : [...form.data.team_ids, teamId],
        );

    const goToReview = () => {
        setStep(2);

        if (form.data.source_season_id === null) {
            return;
        }

        setIsPreviewing(true);
        router.reload({
            only: ['preview'],
            data: {
                source_season_id: form.data.source_season_id,
                age_cutoff_on: form.data.age_cutoff_on,
            },
            onFinish: () => setIsPreviewing(false),
        });
    };

    const submit = () =>
        form.post(store.url(), {
            onError: (errors) => {
                if (
                    errors.name ||
                    errors.starts_on ||
                    errors.ends_on ||
                    errors.age_cutoff_on
                ) {
                    setStep(0);
                }
            },
        });

    const detailsComplete =
        form.data.name.trim() !== '' &&
        form.data.starts_on !== '' &&
        form.data.ends_on !== '' &&
        form.data.age_cutoff_on !== '';

    return (
        <>
            <Head title="Start a new season" />

            <section className="max-w-2xl space-y-8">
                <div>
                    <SectionHeading
                        title="Start a new season"
                        description="Bring your team structure forward. Rosters start empty, because kids move up, change teams or stop playing."
                    />
                    <ol className="mt-4 grid grid-cols-3 border-2 border-ink">
                        {STEPS.map((label, index) => (
                            <li
                                key={label}
                                aria-current={
                                    index === step ? 'step' : undefined
                                }
                                className={cn(
                                    'flex h-11 items-center justify-center gap-2 border-r border-ink label-caps last:border-r-0',
                                    index === step
                                        ? 'bg-ink text-paper'
                                        : index < step
                                          ? 'text-ink'
                                          : 'text-ink-muted',
                                )}
                            >
                                <span className="font-display text-base">
                                    {index + 1}
                                </span>
                                {label}
                            </li>
                        ))}
                    </ol>
                </div>

                {step === 0 && (
                    <div className="grid gap-5">
                        <Field
                            id="rollover-name"
                            label="Season name"
                            error={form.errors.name}
                        >
                            <Input
                                id="rollover-name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                placeholder="Spring 2027"
                                autoFocus
                            />
                        </Field>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                id="rollover-starts"
                                label="Starts"
                                error={form.errors.starts_on}
                            >
                                <Input
                                    id="rollover-starts"
                                    type="date"
                                    value={form.data.starts_on}
                                    onChange={(event) =>
                                        form.setData(
                                            'starts_on',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                id="rollover-ends"
                                label="Ends"
                                error={form.errors.ends_on}
                            >
                                <Input
                                    id="rollover-ends"
                                    type="date"
                                    value={form.data.ends_on}
                                    onChange={(event) =>
                                        form.setData(
                                            'ends_on',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                        <Field
                            id="rollover-cutoff"
                            label="Age cutoff date"
                            hint="Filled in one year after the last season's cutoff."
                            error={form.errors.age_cutoff_on}
                        >
                            <Input
                                id="rollover-cutoff"
                                type="date"
                                value={form.data.age_cutoff_on}
                                onChange={(event) =>
                                    form.setData(
                                        'age_cutoff_on',
                                        event.target.value,
                                    )
                                }
                            />
                        </Field>
                    </div>
                )}

                {step === 1 && (
                    <div className="grid gap-5">
                        <Field
                            id="rollover-source"
                            label="Copy teams from"
                            error={form.errors.source_season_id}
                        >
                            <NativeSelect
                                id="rollover-source"
                                value={form.data.source_season_id ?? ''}
                                onChange={(event) =>
                                    chooseSource(event.target.value)
                                }
                            >
                                <option value="">Don't copy teams</option>
                                {seasons.map((season) => (
                                    <option key={season.id} value={season.id}>
                                        {season.name} ({season.teams.length}{' '}
                                        teams)
                                    </option>
                                ))}
                            </NativeSelect>
                        </Field>

                        {source !== undefined && source.teams.length > 0 && (
                            <fieldset className="space-y-3">
                                <legend className="mb-2 label-caps text-ink-muted">
                                    Teams to copy · {form.data.team_ids.length}{' '}
                                    of {source.teams.length}
                                </legend>
                                <ul className="border-t border-rule">
                                    {source.teams.map((team) => (
                                        <li
                                            key={team.id}
                                            className="border-b border-rule"
                                        >
                                            <label className="flex min-h-12 items-center gap-3 py-2">
                                                <Checkbox
                                                    checked={form.data.team_ids.includes(
                                                        team.id,
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleTeam(team.id)
                                                    }
                                                />
                                                <span className="flex-1 font-semibold">
                                                    {team.name}
                                                </span>
                                                <span className="text-sm text-ink-muted">
                                                    {team.divisionName}
                                                    {team.headCoachName &&
                                                        ` · ${team.headCoachName}`}
                                                </span>
                                            </label>
                                        </li>
                                    ))}
                                </ul>
                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="copy-coaches"
                                        checked={form.data.copy_head_coaches}
                                        onCheckedChange={(checked) =>
                                            form.setData(
                                                'copy_head_coaches',
                                                checked === true,
                                            )
                                        }
                                    />
                                    <Label htmlFor="copy-coaches">
                                        Keep the same head coaches
                                    </Label>
                                </div>
                                {form.errors.team_ids && (
                                    <p className="text-sm font-medium text-danger">
                                        {form.errors.team_ids}
                                    </p>
                                )}
                            </fieldset>
                        )}

                        {source !== undefined && source.teams.length === 0 && (
                            <p className="text-ink-muted">
                                {source.name} has no teams to copy.
                            </p>
                        )}
                    </div>
                )}

                {step === 2 && (
                    <div className="grid gap-6">
                        <dl className="grid gap-x-6 border-t border-rule sm:grid-cols-2">
                            <div className="border-b border-rule py-3">
                                <dt className="label-caps text-ink-muted">
                                    Season
                                </dt>
                                <dd className="mt-1 text-lg">
                                    {form.data.name}
                                </dd>
                            </div>
                            <div className="border-b border-rule py-3">
                                <dt className="label-caps text-ink-muted">
                                    Teams
                                </dt>
                                <dd className="mt-1 text-lg">
                                    {source === undefined
                                        ? 'None, start empty'
                                        : `${form.data.team_ids.length} from ${source.name}`}
                                </dd>
                            </div>
                        </dl>

                        {source !== undefined && (
                            <section className="space-y-3">
                                <h3 className="label-caps text-ink">
                                    Who moves up
                                </h3>
                                {isPreviewing || preview === undefined ? (
                                    <div className="space-y-2">
                                        <Skeleton className="h-5 w-2/3" />
                                        <Skeleton className="h-5 w-1/2" />
                                    </div>
                                ) : preview === null ||
                                  preview.movingUp.length === 0 ? (
                                    <p className="text-ink-muted">
                                        Nobody from {source.name} changes
                                        division under the new age cutoff.
                                    </p>
                                ) : (
                                    <>
                                        <p className="text-ink-muted">
                                            {preview.movingUp.length} of{' '}
                                            {preview.returningCount} players
                                            from {source.name} land in a
                                            different division by league age.
                                        </p>
                                        <ul className="border-t border-rule">
                                            {preview.movingUp.map((change) => (
                                                <li
                                                    key={change.name}
                                                    className="flex items-center justify-between gap-3 border-b border-rule py-2"
                                                >
                                                    <span>{change.name}</span>
                                                    <span className="label-caps text-ink-muted">
                                                        {change.from ?? 'None'}{' '}
                                                        →{' '}
                                                        <span className="text-ink">
                                                            {change.to ??
                                                                'None'}
                                                        </span>
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    </>
                                )}
                            </section>
                        )}

                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="activate"
                                checked={form.data.activate}
                                onCheckedChange={(checked) =>
                                    form.setData('activate', checked === true)
                                }
                            />
                            <Label htmlFor="activate">
                                Make it the active season
                            </Label>
                        </div>
                    </div>
                )}

                <div className="flex flex-wrap items-center gap-3 border-t-2 border-ink pt-6">
                    {step === 0 ? (
                        <Button asChild variant="outline">
                            <Link href={seasonsIndex()}>Cancel</Link>
                        </Button>
                    ) : (
                        <Button
                            variant="outline"
                            onClick={() => setStep(step - 1)}
                        >
                            <ArrowLeft />
                            Back
                        </Button>
                    )}
                    {step === 0 && (
                        <Button
                            onClick={() => setStep(1)}
                            disabled={!detailsComplete}
                        >
                            Next: teams
                            <ArrowRight />
                        </Button>
                    )}
                    {step === 1 && (
                        <Button onClick={goToReview}>
                            Next: review
                            <ArrowRight />
                        </Button>
                    )}
                    {step === 2 && (
                        <Button
                            size="lg"
                            onClick={submit}
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            Start {form.data.name}
                        </Button>
                    )}
                </div>
            </section>
        </>
    );
}
