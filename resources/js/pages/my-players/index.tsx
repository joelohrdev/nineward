import { Head } from '@inertiajs/react';
import { EmptyState } from '@/components/scorebook/empty-state';
import { PageHeader } from '@/components/scorebook/page-header';
import { formatDate } from '@/lib/format';

type Player = {
    id: number;
    name: string;
    dateOfBirth: string;
    position: string | null;
    organizationName: string;
    organizationColor: string | null;
};

export default function MyPlayers({ players }: { players: Player[] }) {
    return (
        <>
            <Head title="My players" />

            <PageHeader
                title="My players"
                context="Your kids, in every organization that uses this app"
            />

            {players.length === 0 ? (
                <EmptyState message="No players are linked to your account yet." />
            ) : (
                <ul className="grid gap-4 sm:grid-cols-2">
                    {players.map((player) => (
                        <li
                            key={player.id}
                            className="border-2 border-ink bg-sheet"
                            style={
                                player.organizationColor
                                    ? {
                                          borderTopColor:
                                              player.organizationColor,
                                          borderTopWidth: 6,
                                      }
                                    : undefined
                            }
                        >
                            <div className="px-4 py-3">
                                <p className="label-caps text-ink-muted">
                                    {player.organizationName}
                                </p>
                                <h2 className="mt-1 font-display text-3xl leading-none uppercase">
                                    {player.name}
                                </h2>
                                <p className="mt-2 text-sm text-ink-muted">
                                    Born {formatDate(player.dateOfBirth)}
                                    {player.position && ` · ${player.position}`}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}
