import { Head, Link } from '@inertiajs/react';
import { JerseyNumber } from '@/components/scorebook/jersey-number';
import PublicLayout from '@/layouts/public-layout';
import type { PublicOrganization } from '@/layouts/public-layout';
import { show as organizationPage } from '@/routes/organizations/public';

type Props = {
    organization: PublicOrganization;
    team: { name: string; divisionName: string; seasonName: string };
    players: { jerseyNumber: number | null; name: string }[];
};

export default function PublicTeam({ organization, team, players }: Props) {
    return (
        <PublicLayout organization={organization}>
            <Head title={`${team.name} · ${organization.name}`} />

            <header className="mb-8 border-b-2 border-ink pb-4">
                <h1 className="font-display text-4xl leading-none uppercase">
                    {team.name}
                </h1>
                <p className="mt-2 text-ink-muted">
                    {team.divisionName} · {team.seasonName}
                </p>
            </header>

            {players.length === 0 ? (
                <p className="text-ink-muted">The roster isn't set yet.</p>
            ) : (
                <ol className="border-t border-rule">
                    {players.map((player, index) => (
                        <li
                            key={index}
                            className="flex items-center gap-4 border-b border-rule py-3"
                        >
                            <JerseyNumber number={player.jerseyNumber} />
                            <span className="font-semibold">{player.name}</span>
                        </li>
                    ))}
                </ol>
            )}

            <p className="mt-8 text-sm">
                <Link
                    href={organizationPage(organization.slug)}
                    className="underline underline-offset-4"
                >
                    {organization.name}
                </Link>
            </p>
        </PublicLayout>
    );
}
