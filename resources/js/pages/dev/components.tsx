import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppearanceTabs from '@/components/appearance-tabs';
import InputError from '@/components/input-error';
import { DataTable } from '@/components/scorebook/data-table';
import { EmptyState } from '@/components/scorebook/empty-state';
import { JerseyNumber } from '@/components/scorebook/jersey-number';
import { LineScore } from '@/components/scorebook/line-score';
import { LineupCard } from '@/components/scorebook/lineup-card';
import { PageHeader } from '@/components/scorebook/page-header';
import { Wordmark } from '@/components/scorebook/platform-mark';
import { SeasonTile } from '@/components/scorebook/season-tile';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';

type SamplePlayer = {
    id: number;
    name: string;
    jersey: number | null;
    position: string;
    status: 'Paid' | 'Unpaid' | 'Flagged';
};

const players: SamplePlayer[] = [
    { id: 1, name: 'Caleb Ortiz', jersey: 7, position: 'C', status: 'Paid' },
    {
        id: 2,
        name: 'Mason Reyes',
        jersey: 12,
        position: 'SS',
        status: 'Unpaid',
    },
    {
        id: 3,
        name: 'Owen Park',
        jersey: null,
        position: 'CF',
        status: 'Flagged',
    },
];

const statusTone = {
    Paid: 'done',
    Unpaid: 'open',
    Flagged: 'flagged',
} as const;

export default function ComponentSheet() {
    const [selected, setSelected] = useState<number[]>([2]);
    const toggle = (id: number) =>
        setSelected((ids) =>
            ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id],
        );

    return (
        <>
            <Head title="Component sheet" />

            <PageHeader
                title="Component sheet"
                context="Every scorebook component and state, for design review. Local only."
                actions={<AppearanceTabs />}
            />

            <div className="space-y-14">
                <section className="space-y-4">
                    <SectionHeading title="Marks" />
                    <div className="flex flex-wrap items-center gap-6">
                        <Wordmark className="text-4xl" />
                        <div className="rounded-sm bg-board p-3">
                            <SeasonTile name="Spring 2026" />
                        </div>
                        <div className="rounded-sm bg-board p-3">
                            <SeasonTile name="Fall 2025" isFinal />
                        </div>
                    </div>
                </section>

                <section className="space-y-4">
                    <SectionHeading title="Line score" />
                    <LineScore
                        entries={[
                            { label: 'Registered', value: 148 },
                            { label: 'Unassigned', value: 14 },
                            { label: 'Rostered', value: 134 },
                            { label: 'Teams', value: 11 },
                            { label: 'Unpaid', value: 3 },
                        ]}
                    />
                </section>

                <section className="space-y-4">
                    <SectionHeading
                        title="Buttons"
                        description="Primary, secondary, outline, ghost, destructive, brand, link"
                    />
                    <div className="flex flex-wrap gap-3">
                        <Button>Save roster</Button>
                        <Button variant="secondary">Export CSV</Button>
                        <Button variant="outline">Cancel</Button>
                        <Button variant="ghost">Skip</Button>
                        <Button variant="destructive">Remove player</Button>
                        <Button variant="brand">Register now</Button>
                        <Button variant="link">View history</Button>
                        <Button disabled>Disabled</Button>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        <Button size="sm">Small</Button>
                        <Button size="lg">Large</Button>
                    </div>
                </section>

                <section className="space-y-4">
                    <SectionHeading title="Status and badges" />
                    <div className="flex flex-wrap items-center gap-6">
                        <StatusMark tone="done">Paid</StatusMark>
                        <StatusMark tone="open">Unpaid</StatusMark>
                        <StatusMark tone="flagged">Needs review</StatusMark>
                        <StatusMark tone="failed">Failed</StatusMark>
                        <StatusMark tone="neutral">Closed</StatusMark>
                        <Badge>Captain</Badge>
                        <Badge variant="secondary">10U</Badge>
                        <Badge variant="outline">Draft</Badge>
                    </div>
                    <div className="flex items-center gap-2">
                        <JerseyNumber number={7} />
                        <JerseyNumber number={23} />
                        <JerseyNumber number={null} />
                    </div>
                </section>

                <section className="space-y-4">
                    <SectionHeading title="Form fields" />
                    <div className="grid max-w-xl gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="sheet-name">Player name</Label>
                            <Input
                                id="sheet-name"
                                placeholder="First and last name"
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="sheet-dob">Date of birth</Label>
                            <Input id="sheet-dob" type="date" aria-invalid />
                            <InputError message="Enter a date of birth." />
                        </div>
                        <div className="flex items-center gap-3">
                            <Checkbox id="sheet-captain" defaultChecked />
                            <Label htmlFor="sheet-captain">Team captain</Label>
                        </div>
                    </div>
                </section>

                <section className="space-y-4">
                    <SectionHeading title="Data table" />
                    <DataTable
                        caption="Sample roster"
                        numbered
                        rows={players}
                        rowKey={(player) => player.id}
                        columns={[
                            {
                                key: 'jersey',
                                header: '#',
                                cell: (player) => (
                                    <JerseyNumber number={player.jersey} />
                                ),
                                className: 'md:w-16',
                            },
                            {
                                key: 'name',
                                header: 'Player',
                                primary: true,
                                cell: (player) => player.name,
                            },
                            {
                                key: 'position',
                                header: 'Pos',
                                cell: (player) => player.position,
                            },
                            {
                                key: 'status',
                                header: 'Fee',
                                cell: (player) => (
                                    <StatusMark
                                        tone={statusTone[player.status]}
                                    >
                                        {player.status}
                                    </StatusMark>
                                ),
                            },
                        ]}
                    />
                </section>

                <section className="space-y-4">
                    <SectionHeading title="Lineup cards" />
                    <div className="grid gap-4 md:grid-cols-2">
                        <LineupCard
                            title="Unassigned · 10U"
                            entries={players.map((player) => ({
                                id: player.id,
                                primary: player.name,
                                secondary: player.position,
                            }))}
                            selectedIds={selected}
                            onToggle={toggle}
                            empty="No unassigned players."
                        />
                        <LineupCard
                            title="10U Red"
                            entries={[]}
                            selectedIds={[]}
                            onToggle={toggle}
                            empty="No players on this team yet."
                        />
                    </div>
                </section>

                <section className="space-y-4">
                    <SectionHeading title="Empty and loading" />
                    <EmptyState
                        message="No teams in Spring 2026 yet."
                        action={<Button>Create a team</Button>}
                    />
                    <div className="space-y-2">
                        <Skeleton className="h-6 w-2/3" />
                        <Skeleton className="h-6 w-1/2" />
                    </div>
                </section>
            </div>
        </>
    );
}
