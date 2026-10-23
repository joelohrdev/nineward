import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { EmptyState } from '@/components/scorebook/empty-state';
import { LineScore } from '@/components/scorebook/line-score';
import { PageHeader } from '@/components/scorebook/page-header';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { index as seasonsIndex } from '@/routes/organization/seasons';
import { index as players } from '@/routes/players';
import { index as teamsIndex, show as showTeam } from '@/routes/seasons/teams';

type Props = {
    season: { id: number; name: string; isActive: boolean } | null;
    activeSeasonName: string | null;
    counts: {
        players: number;
        unassigned: number;
        rostered: number;
        teams: number;
    } | null;
    myTeams: { slug: string; name: string; playersCount: number }[];
    canManage: boolean;
};

function UpNextItem({
    href,
    children,
}: {
    href: string;
    children: React.ReactNode;
}) {
    return (
        <li>
            <Link
                href={href}
                className="flex items-center justify-between gap-4 border-b border-rule py-4 hover:bg-wash/60"
            >
                <span>{children}</span>
                <ArrowRight
                    className="size-4 shrink-0 text-ink-muted"
                    aria-hidden="true"
                />
            </Link>
        </li>
    );
}

export default function Dashboard({
    season,
    activeSeasonName,
    counts,
    myTeams,
    canManage,
}: Props) {
    const { auth } = usePage().props;
    const upNext: { href: string; label: string }[] = [];

    if (canManage && season === null) {
        upNext.push({
            href: seasonsIndex.url(),
            label: 'Create your first season',
        });
    }

    if (season !== null && counts !== null) {
        if (counts.players === 0) {
            upNext.push({
                href: players.url(),
                label: 'Add your first players',
            });
        } else if (counts.teams === 0) {
            upNext.push({
                href: teamsIndex.url(season.id),
                label: `Create teams for ${season.name}`,
            });
        } else if (counts.unassigned > 0) {
            upNext.push({
                href: teamsIndex.url(season.id),
                label: `${counts.unassigned} ${counts.unassigned === 1 ? 'player needs' : 'players need'} a team in ${season.name}`,
            });
        }
    }

    return (
        <>
            <Head title="Home" />

            <PageHeader
                title="Home"
                context={
                    season === null
                        ? `Signed in as ${auth.user.name}`
                        : season.isActive || activeSeasonName === null
                          ? `${season.name} is the active season`
                          : `Showing ${season.name}. The active season is ${activeSeasonName}.`
                }
            />

            <div className="space-y-10">
                {counts !== null && (
                    <LineScore
                        entries={[
                            { label: 'Players', value: counts.players },
                            { label: 'Unassigned', value: counts.unassigned },
                            { label: 'Rostered', value: counts.rostered },
                            { label: 'Teams', value: counts.teams },
                        ]}
                    />
                )}

                {myTeams.length > 0 && season !== null && (
                    <section className="space-y-4">
                        <SectionHeading title="Your teams" />
                        <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {myTeams.map((team) => (
                                <li key={team.slug}>
                                    <Link
                                        href={showTeam({
                                            season: season.id,
                                            team: team.slug,
                                        })}
                                        className="flex items-baseline justify-between border-2 border-ink bg-sheet px-4 py-3 hover:bg-wash"
                                    >
                                        <span className="font-display text-2xl uppercase">
                                            {team.name}
                                        </span>
                                        <span className="label-caps text-ink-muted">
                                            {team.playersCount} players
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="space-y-4">
                    <SectionHeading title="Up next" />
                    {upNext.length === 0 ? (
                        <EmptyState message="Nothing waiting on you right now." />
                    ) : (
                        <ul>
                            {upNext.map((item) => (
                                <UpNextItem key={item.label} href={item.href}>
                                    {item.label}
                                </UpNextItem>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </>
    );
}
