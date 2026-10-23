import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { FormDialog } from '@/components/form-dialog';
import { GuardianFields } from '@/components/players/guardian-fields';
import { DataTable } from '@/components/scorebook/data-table';
import { JerseyNumber } from '@/components/scorebook/jersey-number';
import { PageHeader } from '@/components/scorebook/page-header';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';
import { submissionTones } from '@/lib/submission-status';
import type { SubmissionStatus } from '@/lib/submission-status';
import { show as showSubmission } from '@/routes/submissions';
import { update as updateGuardian } from '@/routes/guardians';
import { store as sendClaimInvitation } from '@/routes/guardians/claim-invitation';
import { destroy, edit } from '@/routes/players';
import { show as showTeam } from '@/routes/seasons/teams';
import {
    destroy as unlinkGuardian,
    store as addGuardian,
} from '@/routes/players/guardians';

type Player = {
    id: number;
    firstName: string;
    lastName: string;
    name: string;
    dateOfBirth: string;
    graduationYear: number | null;
    bats: string | null;
    throws: string | null;
    position: string | null;
    notes: string | null;
    leagueAge: number | null;
    suggestedDivision: string | null;
};

type Guardian = {
    id: number;
    firstName: string;
    lastName: string;
    name: string;
    email: string;
    phone: string | null;
    relationship: string | null;
    isClaimed: boolean;
};

type RosterHistoryEntry = {
    seasonId: number;
    seasonName: string;
    teamName: string;
    teamSlug: string | null;
    divisionName: string;
    jerseyNumber: number | null;
};

type Props = {
    player: Player;
    guardians: Guardian[];
    activeSeasonName: string | null;
    canManage: boolean;
    rosterHistory: RosterHistoryEntry[];
    registrations: {
        id: number;
        formTitle: string;
        status: SubmissionStatus;
        statusLabel: string;
        submittedAt: string | null;
    }[];
};

function Detail({
    label,
    value,
}: {
    label: string;
    value: string | number | null;
}) {
    return (
        <div className="border-b border-rule py-3">
            <dt className="label-caps text-ink-muted">{label}</dt>
            <dd className="mt-1 text-lg">{value ?? '–'}</dd>
        </div>
    );
}

export default function ShowPlayer({
    player,
    guardians,
    activeSeasonName,
    canManage,
    rosterHistory,
    registrations,
}: Props) {
    return (
        <>
            <Head title={player.name} />

            <PageHeader
                title={player.name}
                context={`Born ${formatDate(player.dateOfBirth)}`}
                actions={
                    canManage && (
                        <>
                            <Button asChild variant="outline">
                                <Link href={edit(player.id)}>
                                    <Pencil />
                                    Edit
                                </Link>
                            </Button>
                            <ConfirmDialog
                                title={`Remove ${player.name}?`}
                                description="They leave the master list but nothing is deleted. You can restore them from Removed players."
                                confirmLabel="Remove player"
                                trigger={
                                    <Button variant="ghost">Remove</Button>
                                }
                                form={destroy.form(player.id)}
                            />
                        </>
                    )
                }
            />

            <div className="grid gap-12 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                <section className="space-y-2">
                    <SectionHeading title="Player" />
                    <dl className="grid grid-cols-2 gap-x-6">
                        {activeSeasonName !== null && (
                            <>
                                <Detail
                                    label={`League age · ${activeSeasonName}`}
                                    value={player.leagueAge}
                                />
                                <Detail
                                    label="Suggested division"
                                    value={player.suggestedDivision}
                                />
                            </>
                        )}
                        <Detail label="Bats" value={player.bats} />
                        <Detail label="Throws" value={player.throws} />
                        <Detail label="Position" value={player.position} />
                        <Detail
                            label="Graduates"
                            value={player.graduationYear}
                        />
                    </dl>
                    <div className="pt-8">
                        <SectionHeading title="Teams" />
                        {rosterHistory.length === 0 ? (
                            <p className="py-3 text-sm text-ink-muted">
                                Not on a team yet.
                            </p>
                        ) : (
                            <ol>
                                {rosterHistory.map((entry) => (
                                    <li
                                        key={entry.seasonId}
                                        className="flex items-center gap-3 border-b border-rule py-3"
                                    >
                                        <JerseyNumber
                                            number={entry.jerseyNumber}
                                        />
                                        <span className="min-w-0">
                                            {entry.teamSlug === null ? (
                                                <span className="font-semibold">
                                                    {entry.teamName}
                                                </span>
                                            ) : (
                                                <Link
                                                    href={showTeam({
                                                        season: entry.seasonId,
                                                        team: entry.teamSlug,
                                                    })}
                                                    className="font-semibold underline decoration-rule-strong underline-offset-4 hover:decoration-clay"
                                                >
                                                    {entry.teamName}
                                                </Link>
                                            )}
                                            <span className="block text-sm text-ink-muted">
                                                {entry.seasonName} ·{' '}
                                                {entry.divisionName}
                                            </span>
                                        </span>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </div>
                    {registrations.length > 0 && (
                        <div className="pt-8">
                            <SectionHeading title="Registrations" />
                            <ul>
                                {registrations.map((registration) => (
                                    <li
                                        key={registration.id}
                                        className="flex items-center justify-between gap-3 border-b border-rule py-3"
                                    >
                                        <Link
                                            href={showSubmission(
                                                registration.id,
                                            )}
                                            className="font-semibold underline decoration-rule-strong underline-offset-4 hover:decoration-clay"
                                        >
                                            {registration.formTitle}
                                        </Link>
                                        <span className="flex items-center gap-3 text-sm text-ink-muted">
                                            {formatDate(
                                                registration.submittedAt,
                                            )}
                                            <StatusMark
                                                tone={
                                                    submissionTones[
                                                        registration.status
                                                    ]
                                                }
                                            >
                                                {registration.statusLabel}
                                            </StatusMark>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                    {player.notes && (
                        <div className="pt-4">
                            <p className="label-caps text-ink-muted">Notes</p>
                            <p className="mt-1 whitespace-pre-line">
                                {player.notes}
                            </p>
                        </div>
                    )}
                </section>

                <section className="min-w-0 space-y-4">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div className="min-w-0 flex-1">
                            <SectionHeading
                                title="Parents and guardians"
                                description="Claimed guardians have their own login and can see this player."
                            />
                        </div>
                        {canManage && (
                            <FormDialog
                                title="Add a guardian"
                                description="If someone in your list already has this email, they're linked instead of duplicated."
                                trigger={
                                    <Button variant="secondary">
                                        <Plus />
                                        Add guardian
                                    </Button>
                                }
                                form={addGuardian.form(player.id)}
                                submitLabel="Add guardian"
                            >
                                {(errors) => (
                                    <GuardianFields
                                        errors={errors}
                                        idPrefix="new-guardian"
                                    />
                                )}
                            </FormDialog>
                        )}
                    </div>

                    <DataTable
                        caption="Guardians"
                        rows={guardians}
                        rowKey={(guardian) => guardian.id}
                        columns={[
                            {
                                key: 'name',
                                header: 'Name',
                                primary: true,
                                cell: (guardian) => (
                                    <span className="grid">
                                        {guardian.name}
                                        {guardian.relationship && (
                                            <span className="text-sm font-normal text-ink-muted">
                                                {guardian.relationship}
                                            </span>
                                        )}
                                    </span>
                                ),
                            },
                            {
                                key: 'contact',
                                header: 'Contact',
                                cell: (guardian) => (
                                    <span className="grid">
                                        <a
                                            href={`mailto:${guardian.email}`}
                                            className="underline decoration-rule-strong underline-offset-4"
                                        >
                                            {guardian.email}
                                        </a>
                                        {guardian.phone && (
                                            <a href={`tel:${guardian.phone}`}>
                                                {guardian.phone}
                                            </a>
                                        )}
                                    </span>
                                ),
                            },
                            {
                                key: 'account',
                                header: 'Account',
                                cell: (guardian) =>
                                    guardian.isClaimed ? (
                                        <StatusMark tone="done">
                                            Claimed
                                        </StatusMark>
                                    ) : !canManage ? (
                                        <StatusMark tone="open">
                                            Not yet
                                        </StatusMark>
                                    ) : (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <Link
                                                href={sendClaimInvitation(
                                                    guardian.id,
                                                )}
                                                as="button"
                                                preserveScroll
                                            >
                                                Send account invite
                                            </Link>
                                        </Button>
                                    ),
                            },
                            {
                                key: 'actions',
                                header: 'Actions',
                                className: 'md:text-right',
                                cell: (guardian) =>
                                    !canManage ? null : (
                                        <span className="inline-flex gap-1 md:justify-end">
                                            <FormDialog
                                                title={`Edit ${guardian.name}`}
                                                description="Changes apply everywhere this guardian appears, including siblings."
                                                trigger={
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                    >
                                                        Edit
                                                    </Button>
                                                }
                                                form={updateGuardian.form(
                                                    guardian.id,
                                                )}
                                                submitLabel="Save guardian"
                                            >
                                                {(errors) => (
                                                    <GuardianFields
                                                        errors={errors}
                                                        idPrefix={`guardian-${guardian.id}`}
                                                        guardian={guardian}
                                                        showRelationship={false}
                                                    />
                                                )}
                                            </FormDialog>
                                            <ConfirmDialog
                                                title={`Unlink ${guardian.name}?`}
                                                description={`They stay in your list but are no longer listed for ${player.firstName}.`}
                                                confirmLabel="Unlink"
                                                trigger={
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                    >
                                                        Unlink
                                                    </Button>
                                                }
                                                form={unlinkGuardian.form({
                                                    player: player.id,
                                                    guardian: guardian.id,
                                                })}
                                            />
                                        </span>
                                    ),
                            },
                        ]}
                    />
                </section>
            </div>
        </>
    );
}
