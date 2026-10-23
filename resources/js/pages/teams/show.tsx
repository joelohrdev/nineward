import { Head, router, usePage } from '@inertiajs/react';
import { ArrowRight, Download, Pencil, Printer } from 'lucide-react';
import { useMemo, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import InputError from '@/components/input-error';
import { EmptyState } from '@/components/scorebook/empty-state';
import { JerseyNumber } from '@/components/scorebook/jersey-number';
import { LineupCard } from '@/components/scorebook/lineup-card';
import { PageHeader } from '@/components/scorebook/page-header';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { TeamFields } from '@/components/teams/team-fields';
import type {
    CoachOption,
    DivisionOption,
} from '@/components/teams/team-fields';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { cn } from '@/lib/utils';
import { show as showPlayer } from '@/routes/players';
import { destroy, update } from '@/routes/seasons/teams';
import {
    destroy as removeEntry,
    store as addEntries,
    update as updateEntry,
} from '@/routes/teams/roster-entries';
import { store as transferEntry } from '@/routes/teams/roster-entries/transfer';
import { show as printRoster } from '@/routes/teams/roster-pdf';
import { show as exportRoster } from '@/routes/teams/roster-export';

const POSITIONS = ['P', 'C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'UT'];

type RosterRow = {
    id: number;
    playerId: number;
    name: string;
    leagueAge: number;
    suggestedDivision: string | null;
    jerseyNumber: number | null;
    position: string | null;
    isCaptain: boolean;
    guardians: { name: string; email: string; phone: string | null }[];
};

type PoolRow = {
    id: number;
    name: string;
    leagueAge: number;
    suggestedDivision: string | null;
    fitsDivision: boolean;
};

type Props = {
    season: { id: number; name: string; hasEnded: boolean };
    team: {
        id: number;
        slug: string;
        name: string;
        divisionId: number;
        divisionName: string;
        headCoachId: number | null;
        headCoachName: string | null;
    };
    roster: RosterRow[];
    pool: PoolRow[];
    otherTeams: { id: number; name: string }[];
    canManageRoster: boolean;
    canExport: boolean;
    canManageTeam: boolean;
    divisions: DivisionOption[];
    coaches: CoachOption[];
};

function RosterEntryRow({
    entry,
    teamId,
    divisionName,
    otherTeams,
    canManage,
}: {
    entry: RosterRow;
    teamId: number;
    divisionName: string;
    otherTeams: { id: number; name: string }[];
    canManage: boolean;
}) {
    const [jersey, setJersey] = useState(entry.jerseyNumber?.toString() ?? '');
    const [error, setError] = useState<string | undefined>();

    const save = (
        changes: Partial<
            Pick<RosterRow, 'jerseyNumber' | 'position' | 'isCaptain'>
        >,
    ) => {
        const next = { ...entry, ...changes };

        router.put(
            updateEntry.url({ team: teamId, rosterEntry: entry.id }),
            {
                jersey_number: next.jerseyNumber,
                position: next.position,
                is_captain: next.isCaptain,
            },
            {
                preserveScroll: true,
                onSuccess: () => setError(undefined),
                onError: (errors) => {
                    setError(errors.jersey_number ?? errors.position);
                    setJersey(entry.jerseyNumber?.toString() ?? '');
                },
            },
        );
    };

    const saveJersey = () => {
        const value = jersey.trim() === '' ? null : Number(jersey);

        if (value !== entry.jerseyNumber) {
            save({ jerseyNumber: value });
        }
    };

    const isOffDivision =
        entry.suggestedDivision !== null &&
        entry.suggestedDivision !== divisionName;

    return (
        <li className="grid grid-cols-[3.25rem_minmax(0,1fr)] gap-x-3 gap-y-2 border-b border-rule py-3 sm:grid-cols-[3.25rem_minmax(0,1fr)_auto] sm:items-center">
            <div className="row-span-2 flex items-start sm:row-span-1">
                {canManage ? (
                    <Input
                        aria-label={`Jersey number for ${entry.name}`}
                        inputMode="numeric"
                        value={jersey}
                        onChange={(event) =>
                            setJersey(
                                event.target.value
                                    .replace(/\D/g, '')
                                    .slice(0, 2),
                            )
                        }
                        onBlur={saveJersey}
                        onKeyDown={(event) =>
                            event.key === 'Enter' && event.currentTarget.blur()
                        }
                        placeholder="–"
                        className="h-11 w-13 border-ink/80 px-0 text-center font-display text-xl md:h-11"
                    />
                ) : (
                    <JerseyNumber
                        number={entry.jerseyNumber}
                        className="size-11"
                    />
                )}
            </div>

            <div className="min-w-0">
                <a
                    href={showPlayer.url(entry.playerId)}
                    className="font-semibold underline decoration-rule-strong underline-offset-4 hover:decoration-clay"
                >
                    {entry.name}
                </a>
                {entry.isCaptain && <Badge className="ml-2">Captain</Badge>}
                <p className="mt-0.5 text-sm text-ink-muted">
                    League age {entry.leagueAge}
                    {isOffDivision && (
                        <span className="ml-2 inline-flex items-center gap-1 text-ink">
                            <span
                                aria-hidden="true"
                                className="size-2 rotate-45 bg-warn"
                            />
                            Suggested division {entry.suggestedDivision}
                        </span>
                    )}
                </p>
                {entry.guardians.length > 0 && (
                    <p className="mt-1 text-sm text-ink-muted">
                        {entry.guardians.map((guardian, index) => (
                            <span key={guardian.email}>
                                {index > 0 && ' · '}
                                {guardian.name}{' '}
                                {guardian.phone ? (
                                    <a
                                        href={`tel:${guardian.phone}`}
                                        className="underline underline-offset-2"
                                    >
                                        {guardian.phone}
                                    </a>
                                ) : (
                                    <a
                                        href={`mailto:${guardian.email}`}
                                        className="underline underline-offset-2"
                                    >
                                        {guardian.email}
                                    </a>
                                )}
                            </span>
                        ))}
                    </p>
                )}
                <InputError message={error} className="mt-1" />
            </div>

            {canManage && (
                <div className="col-start-2 flex flex-wrap items-center gap-2 sm:col-start-3">
                    <NativeSelect
                        aria-label={`Position for ${entry.name}`}
                        value={entry.position ?? ''}
                        onChange={(event) =>
                            save({ position: event.target.value || null })
                        }
                        className="h-9 w-20 md:h-9"
                    >
                        <option value="">Pos</option>
                        {POSITIONS.map((position) => (
                            <option key={position} value={position}>
                                {position}
                            </option>
                        ))}
                    </NativeSelect>
                    <label className="flex h-9 items-center gap-2 text-sm">
                        <Checkbox
                            checked={entry.isCaptain}
                            onCheckedChange={(checked) =>
                                save({ isCaptain: checked === true })
                            }
                            aria-label={`${entry.name} is a captain`}
                        />
                        <span className="label-caps">Capt</span>
                    </label>
                    {otherTeams.length > 0 && (
                        <FormDialog
                            title={`Move ${entry.name}`}
                            description="Their jersey number and position stay with this team."
                            trigger={
                                <Button size="sm" variant="ghost">
                                    Move
                                </Button>
                            }
                            form={transferEntry.form({
                                team: teamId,
                                rosterEntry: entry.id,
                            })}
                            submitLabel="Move player"
                        >
                            {(errors) => (
                                <Field
                                    id={`move-${entry.id}`}
                                    label="New team"
                                    error={errors.team_id}
                                >
                                    <NativeSelect
                                        id={`move-${entry.id}`}
                                        name="team_id"
                                        defaultValue=""
                                        required
                                    >
                                        <option value="" disabled>
                                            Choose a team
                                        </option>
                                        {otherTeams.map((other) => (
                                            <option
                                                key={other.id}
                                                value={other.id}
                                            >
                                                {other.name}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                </Field>
                            )}
                        </FormDialog>
                    )}
                    <ConfirmDialog
                        title={`Take ${entry.name} off the team?`}
                        description="They go back to the list of players without a team this season."
                        confirmLabel="Take off team"
                        trigger={
                            <Button size="sm" variant="ghost">
                                Remove
                            </Button>
                        }
                        form={removeEntry.form({
                            team: teamId,
                            rosterEntry: entry.id,
                        })}
                    />
                </div>
            )}
        </li>
    );
}

export default function TeamRoster({
    season,
    team,
    roster,
    pool,
    otherTeams,
    canManageRoster,
    canExport,
    canManageTeam,
    divisions,
    coaches,
}: Props) {
    const { errors } = usePage().props;
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [search, setSearch] = useState('');
    const [fitsOnly, setFitsOnly] = useState(false);
    const [mobileTab, setMobileTab] = useState<'pool' | 'roster'>('roster');

    const visiblePool = useMemo(() => {
        const term = search.trim().toLowerCase();

        return pool.filter(
            (player) =>
                (!fitsOnly || player.fitsDivision) &&
                (term === '' || player.name.toLowerCase().includes(term)),
        );
    }, [pool, search, fitsOnly]);

    const toggle = (id: number) =>
        setSelectedIds((ids) =>
            ids.includes(id)
                ? ids.filter((selected) => selected !== id)
                : [...ids, id],
        );

    const addSelected = () => {
        const moving = pool.filter((player) => selectedIds.includes(player.id));

        router.post(
            addEntries.url(team.id),
            { player_ids: selectedIds },
            {
                preserveScroll: true,
                optimistic: (props) => ({
                    pool: (props.pool as PoolRow[]).filter(
                        (player) => !selectedIds.includes(player.id),
                    ),
                    roster: [
                        ...(props.roster as RosterRow[]),
                        ...moving.map((player) => ({
                            id: -player.id,
                            playerId: player.id,
                            name: player.name,
                            leagueAge: player.leagueAge,
                            suggestedDivision: player.suggestedDivision,
                            jerseyNumber: null,
                            position: null,
                            isCaptain: false,
                            guardians: [],
                        })),
                    ],
                }),
                onSuccess: () => {
                    setSelectedIds([]);
                    setMobileTab('roster');
                },
            },
        );
    };

    const context = [
        team.divisionName,
        team.headCoachName ? `Coach ${team.headCoachName}` : 'No head coach',
        `${roster.length} ${roster.length === 1 ? 'player' : 'players'}`,
    ].join(' · ');

    return (
        <>
            <Head title={`${team.name} · ${season.name}`} />

            <PageHeader
                title={team.name}
                context={context}
                actions={
                    <>
                        {canExport && roster.length > 0 && (
                            <Button asChild variant="outline">
                                <a href={exportRoster.url(team.id)} download>
                                    <Download />
                                    CSV
                                </a>
                            </Button>
                        )}
                        {canExport && roster.length > 0 && (
                            <Button asChild variant="outline">
                                <a href={printRoster.url(team.id)} download>
                                    <Printer />
                                    PDF
                                </a>
                            </Button>
                        )}
                        {canManageTeam && (
                            <FormDialog
                                title={`Edit ${team.name}`}
                                trigger={
                                    <Button variant="outline">
                                        <Pencil />
                                        Edit team
                                    </Button>
                                }
                                form={update.form({
                                    season: season.id,
                                    team: team.slug,
                                })}
                                submitLabel="Save team"
                            >
                                {(formErrors) => (
                                    <TeamFields
                                        errors={formErrors}
                                        divisions={divisions}
                                        coaches={coaches}
                                        idPrefix="edit-team"
                                        team={team}
                                    />
                                )}
                            </FormDialog>
                        )}
                        {canManageTeam && (
                            <ConfirmDialog
                                title={`Delete ${team.name}?`}
                                description="Only teams with no players can be deleted."
                                confirmLabel="Delete team"
                                trigger={
                                    <Button variant="ghost">Delete</Button>
                                }
                                form={destroy.form({
                                    season: season.id,
                                    team: team.slug,
                                })}
                            />
                        )}
                    </>
                }
            />

            {season.hasEnded && !canManageRoster && (
                <p className="mb-6 border-l-4 border-rule-strong bg-sheet px-3 py-2">
                    {season.name} is over, so this roster is read-only.
                </p>
            )}

            {canManageRoster && (
                <div
                    role="tablist"
                    aria-label="Roster builder"
                    className="mb-4 grid grid-cols-2 border-2 border-ink lg:hidden"
                >
                    {(['roster', 'pool'] as const).map((tab) => (
                        <button
                            key={tab}
                            type="button"
                            role="tab"
                            aria-selected={mobileTab === tab}
                            onClick={() => setMobileTab(tab)}
                            className={cn(
                                'h-11 label-caps',
                                mobileTab === tab
                                    ? 'bg-ink text-paper'
                                    : 'text-ink',
                            )}
                        >
                            {tab === 'roster'
                                ? `Roster · ${roster.length}`
                                : `Available · ${pool.length}`}
                        </button>
                    ))}
                </div>
            )}

            <div
                className={cn(
                    'grid gap-8',
                    canManageRoster &&
                        'lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]',
                )}
            >
                {canManageRoster && (
                    <section
                        className={cn(
                            'min-w-0 space-y-3',
                            mobileTab !== 'pool' && 'hidden lg:block',
                        )}
                    >
                        <SectionHeading
                            title={`Available in ${season.name}`}
                            description="Players with no team this season. Select some, then add them."
                        />
                        <div className="flex flex-wrap items-center gap-3">
                            <Input
                                type="search"
                                aria-label="Filter available players"
                                placeholder="Filter by name"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                className="min-w-40 flex-1"
                            />
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="fits-only"
                                    checked={fitsOnly}
                                    onCheckedChange={(checked) =>
                                        setFitsOnly(checked === true)
                                    }
                                />
                                <Label htmlFor="fits-only">
                                    Fits {team.divisionName}
                                </Label>
                            </div>
                        </div>
                        <LineupCard
                            title="Available"
                            count={visiblePool.length}
                            entries={visiblePool.map((player) => ({
                                id: player.id,
                                primary: player.name,
                                secondary: `League age ${player.leagueAge}${player.suggestedDivision ? ` · ${player.suggestedDivision}` : ''}`,
                                leading: player.fitsDivision ? null : (
                                    <span
                                        aria-label="Outside this division's ages"
                                        className="block size-2 rotate-45 bg-warn"
                                    />
                                ),
                            }))}
                            selectedIds={selectedIds}
                            onToggle={toggle}
                            empty={
                                pool.length === 0
                                    ? 'Every player has a team this season.'
                                    : 'No players match.'
                            }
                        />
                        <InputError message={errors.player_ids} />
                        <div className="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] md:bottom-4">
                            <Button
                                size="lg"
                                className="w-full"
                                disabled={selectedIds.length === 0}
                                onClick={addSelected}
                            >
                                Add {selectedIds.length || ''} to {team.name}
                                <ArrowRight />
                            </Button>
                        </div>
                    </section>
                )}

                <section
                    className={cn(
                        'min-w-0 space-y-3',
                        canManageRoster &&
                            mobileTab !== 'roster' &&
                            'hidden lg:block',
                    )}
                >
                    <SectionHeading
                        title="Roster"
                        description={
                            canManageRoster
                                ? 'Jersey numbers save when you leave the box.'
                                : undefined
                        }
                    />
                    {roster.length === 0 ? (
                        <EmptyState message="No players on this team yet." />
                    ) : (
                        <ol className="border-t-2 border-ink">
                            {roster.map((entry) => (
                                <RosterEntryRow
                                    key={`${entry.id}-${entry.jerseyNumber}`}
                                    entry={entry}
                                    teamId={team.id}
                                    divisionName={team.divisionName}
                                    otherTeams={otherTeams}
                                    canManage={canManageRoster && entry.id > 0}
                                />
                            ))}
                        </ol>
                    )}
                </section>
            </div>
        </>
    );
}
