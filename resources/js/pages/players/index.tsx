import { Form, Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { DataTable } from '@/components/scorebook/data-table';
import { EmptyState } from '@/components/scorebook/empty-state';
import { LineScore } from '@/components/scorebook/line-score';
import { PageHeader } from '@/components/scorebook/page-header';
import { Pagination } from '@/components/scorebook/pagination';
import type { Paginated } from '@/components/scorebook/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Skeleton } from '@/components/ui/skeleton';
import { formatDate } from '@/lib/format';
import { create, index, show } from '@/routes/players';
import { store as restorePlayer } from '@/routes/players/restoration';

type PlayerRow = {
    id: number;
    name: string;
    dateOfBirth: string;
    leagueAge: number | null;
    suggestedDivision: string | null;
    batsThrows: string | null;
    position: string | null;
    guardians: string[];
    seasonsPlayed: number;
    isRemoved: boolean;
};

type Props = {
    players: Paginated<PlayerRow>;
    filters: { search: string; removed: boolean; roster: string };
    activeSeasonName: string | null;
    counts?: { players: number; removed: number; unclaimedGuardians: number };
};

export default function Players({
    players,
    filters,
    activeSeasonName,
    counts,
}: Props) {
    const isFiltered =
        filters.search !== '' || filters.removed || filters.roster !== '';

    return (
        <>
            <Head title="Players" />

            <PageHeader
                title="Players"
                context="Every kid who has played for your organization, across all seasons"
                actions={
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Add player
                        </Link>
                    </Button>
                }
            />

            <div className="space-y-8">
                {counts === undefined ? (
                    <Skeleton className="h-24 w-full" />
                ) : (
                    <LineScore
                        entries={[
                            { label: 'Players', value: counts.players },
                            {
                                label: 'Unclaimed guardians',
                                value: counts.unclaimedGuardians,
                            },
                            { label: 'Removed', value: counts.removed },
                        ]}
                    />
                )}

                <Form
                    {...index.form()}
                    options={{ preserveState: true }}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="grid min-w-64 flex-1 gap-2">
                        <Label htmlFor="search">Search</Label>
                        <Input
                            id="search"
                            name="search"
                            type="search"
                            defaultValue={filters.search}
                            placeholder="Player or guardian name, or guardian email"
                        />
                    </div>
                    {activeSeasonName !== null && (
                        <div className="grid gap-2">
                            <Label htmlFor="roster">{activeSeasonName}</Label>
                            <NativeSelect
                                id="roster"
                                name="roster"
                                defaultValue={filters.roster}
                            >
                                <option value="">All players</option>
                                <option value="unassigned">
                                    Not on a team
                                </option>
                                <option value="rostered">On a team</option>
                            </NativeSelect>
                        </div>
                    )}
                    <div className="flex h-10 items-center gap-2">
                        <Checkbox
                            id="removed"
                            name="removed"
                            value="1"
                            defaultChecked={filters.removed}
                        />
                        <Label htmlFor="removed">Removed players</Label>
                    </div>
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </Form>

                {players.data.length === 0 ? (
                    <EmptyState
                        message={
                            isFiltered
                                ? 'No players match.'
                                : 'No players yet. Add your first player, or wait for registrations to fill the list.'
                        }
                        action={
                            isFiltered ? undefined : (
                                <Button asChild>
                                    <Link href={create()}>Add player</Link>
                                </Button>
                            )
                        }
                    />
                ) : (
                    <DataTable
                        caption="Players"
                        rows={players.data}
                        rowKey={(player) => player.id}
                        columns={[
                            {
                                key: 'name',
                                header: 'Player',
                                primary: true,
                                cell: (player) => (
                                    <Link
                                        href={show(player.id)}
                                        className="underline decoration-rule-strong underline-offset-4 hover:decoration-clay"
                                    >
                                        {player.name}
                                    </Link>
                                ),
                            },
                            {
                                key: 'born',
                                header: 'Born',
                                cell: (player) =>
                                    formatDate(player.dateOfBirth),
                            },
                            ...(activeSeasonName === null
                                ? []
                                : [
                                      {
                                          key: 'age',
                                          header: `Age · ${activeSeasonName}`,
                                          cell: (player: PlayerRow) => (
                                              <span className="inline-flex items-center gap-2">
                                                  {player.leagueAge}
                                                  {player.suggestedDivision && (
                                                      <Badge variant="secondary">
                                                          {
                                                              player.suggestedDivision
                                                          }
                                                      </Badge>
                                                  )}
                                              </span>
                                          ),
                                      },
                                  ]),
                            {
                                key: 'bt',
                                header: 'B/T',
                                cell: (player) => player.batsThrows ?? '–',
                            },
                            {
                                key: 'position',
                                header: 'Pos',
                                cell: (player) => player.position ?? '–',
                            },
                            {
                                key: 'seasons',
                                header: 'Seasons',
                                cell: (player) => player.seasonsPlayed,
                            },
                            {
                                key: 'guardians',
                                header: 'Guardians',
                                cell: (player) =>
                                    player.guardians.join(', ') || '–',
                            },
                            ...(filters.removed
                                ? [
                                      {
                                          key: 'restore',
                                          header: 'Actions',
                                          className: 'md:text-right',
                                          cell: (player: PlayerRow) => (
                                              <Button
                                                  asChild
                                                  size="sm"
                                                  variant="ghost"
                                              >
                                                  <Link
                                                      href={restorePlayer(
                                                          player.id,
                                                      )}
                                                      as="button"
                                                  >
                                                      Restore
                                                  </Link>
                                              </Button>
                                          ),
                                      },
                                  ]
                                : []),
                        ]}
                    />
                )}

                <Pagination page={players} />
            </div>
        </>
    );
}
