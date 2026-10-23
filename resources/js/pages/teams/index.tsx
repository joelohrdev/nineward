import { Head, Link } from '@inertiajs/react';
import { Plus, Printer } from 'lucide-react';
import { FormDialog } from '@/components/form-dialog';
import { DataTable } from '@/components/scorebook/data-table';
import { EmptyState } from '@/components/scorebook/empty-state';
import { PageHeader } from '@/components/scorebook/page-header';
import { TeamFields } from '@/components/teams/team-fields';
import type {
    CoachOption,
    DivisionOption,
} from '@/components/teams/team-fields';
import { Button } from '@/components/ui/button';
import { index as divisionsIndex } from '@/routes/organization/divisions';
import { store as rosterPacket } from '@/routes/seasons/roster-packet';
import { show, store } from '@/routes/seasons/teams';

type Team = {
    id: number;
    slug: string;
    name: string;
    divisionName: string;
    headCoachName: string | null;
    playersCount: number;
};

type Props = {
    season: { id: number; name: string; hasEnded: boolean };
    teams: Team[];
    unassignedCount: number | null;
    canManageTeams: boolean;
    divisions: DivisionOption[];
    coaches: CoachOption[];
};

export default function Teams({
    season,
    teams,
    unassignedCount,
    canManageTeams,
    divisions,
    coaches,
}: Props) {
    const newTeamButton =
        canManageTeams && divisions.length > 0 ? (
            <FormDialog
                title={`New team for ${season.name}`}
                trigger={
                    <Button>
                        <Plus />
                        New team
                    </Button>
                }
                form={store.form(season.id)}
                submitLabel="Create team"
            >
                {(errors) => (
                    <TeamFields
                        errors={errors}
                        divisions={divisions}
                        coaches={coaches}
                        idPrefix="new-team"
                    />
                )}
            </FormDialog>
        ) : null;

    return (
        <>
            <Head title={`Teams · ${season.name}`} />

            <PageHeader
                title="Teams"
                context={
                    unassignedCount === null
                        ? season.name
                        : `${season.name} · ${unassignedCount} ${unassignedCount === 1 ? 'player' : 'players'} not on a team yet`
                }
                actions={
                    <>
                        {canManageTeams && teams.length > 0 && (
                            <Button asChild variant="outline">
                                <Link
                                    href={rosterPacket(season.id)}
                                    as="button"
                                    preserveScroll
                                >
                                    <Printer />
                                    Email me all rosters (PDF)
                                </Link>
                            </Button>
                        )}
                        {newTeamButton}
                    </>
                }
            />

            {canManageTeams && divisions.length === 0 && (
                <p className="mb-6 border-l-4 border-warn bg-sheet px-3 py-2">
                    Add your divisions first. Every team belongs to one.{' '}
                    <Link
                        href={divisionsIndex()}
                        className="font-semibold underline underline-offset-4"
                    >
                        Set up divisions
                    </Link>
                </p>
            )}

            {teams.length === 0 ? (
                <EmptyState
                    message={
                        canManageTeams
                            ? `No teams in ${season.name} yet.`
                            : `You're not the head coach of a team in ${season.name}.`
                    }
                    action={newTeamButton ?? undefined}
                />
            ) : (
                <DataTable
                    caption={`Teams in ${season.name}`}
                    rows={teams}
                    rowKey={(team) => team.id}
                    columns={[
                        {
                            key: 'name',
                            header: 'Team',
                            primary: true,
                            cell: (team) => (
                                <Link
                                    href={show({
                                        season: season.id,
                                        team: team.slug,
                                    })}
                                    className="underline decoration-rule-strong underline-offset-4 hover:decoration-clay"
                                >
                                    {team.name}
                                </Link>
                            ),
                        },
                        {
                            key: 'division',
                            header: 'Division',
                            cell: (team) => team.divisionName,
                        },
                        {
                            key: 'coach',
                            header: 'Head coach',
                            cell: (team) => team.headCoachName ?? '–',
                        },
                        {
                            key: 'players',
                            header: 'Players',
                            className: 'md:text-right',
                            cell: (team) => (
                                <span className="font-display text-xl">
                                    {team.playersCount}
                                </span>
                            ),
                        },
                    ]}
                />
            )}
        </>
    );
}
