import { Form, Head, Link, router } from '@inertiajs/react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Field } from '@/components/field';
import { DataTable } from '@/components/scorebook/data-table';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { formatDate } from '@/lib/format';
import {
    destroy as destroyInvitation,
    store as storeInvitation,
} from '@/routes/organization/invitations';
import { destroy, update } from '@/routes/organization/members';
import type { OrganizationRole } from '@/types';

type Member = {
    id: number;
    name: string;
    email: string;
    role: OrganizationRole;
    isOwner: boolean;
    isYou: boolean;
    joinedAt: string | null;
};

type Invitation = {
    id: number;
    email: string;
    role: Exclude<OrganizationRole, 'owner'>;
    expiresAt: string;
    hasExpired: boolean;
};

const roleLabels: Record<OrganizationRole, string> = {
    owner: 'Owner',
    admin: 'Admin',
    coach: 'Coach',
};

function RoleOptions() {
    return (
        <>
            <option value="coach">Coach</option>
            <option value="admin">Admin</option>
        </>
    );
}

export default function Members({
    members,
    invitations,
}: {
    members: Member[];
    invitations: Invitation[];
}) {
    return (
        <>
            <Head title="Members" />

            <section className="space-y-4">
                <SectionHeading
                    title="Invite someone"
                    description="Admins manage the organization. Coaches work with their own teams."
                />
                <Form
                    {...storeInvitation.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="grid gap-4 sm:grid-cols-[minmax(0,1fr)_10rem_auto] sm:items-start"
                >
                    {({ errors, processing, recentlySuccessful }) => (
                        <>
                            <Field
                                id="invite-email"
                                label="Email"
                                error={errors.email}
                            >
                                <Input
                                    id="invite-email"
                                    name="email"
                                    type="email"
                                    autoComplete="off"
                                    placeholder="coach@example.com"
                                    required
                                />
                            </Field>
                            <Field
                                id="invite-role"
                                label="Role"
                                error={errors.role}
                            >
                                <NativeSelect
                                    id="invite-role"
                                    name="role"
                                    defaultValue="coach"
                                >
                                    <RoleOptions />
                                </NativeSelect>
                            </Field>
                            <div className="sm:pt-[1.375rem]">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Send invite
                                </Button>
                            </div>
                            {recentlySuccessful && (
                                <p className="text-sm font-medium text-grass sm:col-span-3">
                                    Invitation sent.
                                </p>
                            )}
                        </>
                    )}
                </Form>
            </section>

            {invitations.length > 0 && (
                <section className="space-y-4">
                    <SectionHeading title="Pending invitations" />
                    <DataTable
                        caption="Pending invitations"
                        rows={invitations}
                        rowKey={(invitation) => invitation.id}
                        columns={[
                            {
                                key: 'email',
                                header: 'Email',
                                primary: true,
                                cell: (invitation) => invitation.email,
                            },
                            {
                                key: 'role',
                                header: 'Role',
                                cell: (invitation) =>
                                    roleLabels[invitation.role],
                            },
                            {
                                key: 'expires',
                                header: 'Expires',
                                cell: (invitation) =>
                                    invitation.hasExpired ? (
                                        <StatusMark tone="failed">
                                            Expired
                                        </StatusMark>
                                    ) : (
                                        formatDate(invitation.expiresAt)
                                    ),
                            },
                            {
                                key: 'actions',
                                header: 'Actions',
                                className: 'md:text-right',
                                cell: (invitation) => (
                                    <span className="inline-flex gap-1 md:justify-end">
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="ghost"
                                        >
                                            <Link
                                                href={storeInvitation()}
                                                data={{
                                                    email: invitation.email,
                                                    role: invitation.role,
                                                }}
                                                as="button"
                                                preserveScroll
                                            >
                                                Resend
                                            </Link>
                                        </Button>
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="ghost"
                                        >
                                            <Link
                                                href={destroyInvitation(
                                                    invitation.id,
                                                )}
                                                as="button"
                                                preserveScroll
                                            >
                                                Revoke
                                            </Link>
                                        </Button>
                                    </span>
                                ),
                            },
                        ]}
                    />
                </section>
            )}

            <section className="space-y-4">
                <SectionHeading title="Members" />
                <DataTable
                    caption="Members"
                    rows={members}
                    rowKey={(member) => member.id}
                    columns={[
                        {
                            key: 'name',
                            header: 'Name',
                            primary: true,
                            cell: (member) => (
                                <span className="inline-flex flex-wrap items-center gap-2">
                                    {member.name}
                                    {member.isYou && (
                                        <Badge variant="secondary">You</Badge>
                                    )}
                                </span>
                            ),
                        },
                        {
                            key: 'email',
                            header: 'Email',
                            cell: (member) => member.email,
                        },
                        {
                            key: 'role',
                            header: 'Role',
                            cell: (member) =>
                                member.isOwner || member.isYou ? (
                                    roleLabels[member.role]
                                ) : (
                                    <NativeSelect
                                        aria-label={`Role for ${member.name}`}
                                        defaultValue={member.role}
                                        className="h-9 md:h-9"
                                        onChange={(event) =>
                                            router.put(
                                                update.url(member.id),
                                                { role: event.target.value },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <RoleOptions />
                                    </NativeSelect>
                                ),
                        },
                        {
                            key: 'joined',
                            header: 'Joined',
                            cell: (member) => formatDate(member.joinedAt),
                        },
                        {
                            key: 'actions',
                            header: 'Actions',
                            className: 'md:text-right',
                            cell: (member) =>
                                member.isOwner || member.isYou ? null : (
                                    <ConfirmDialog
                                        title={`Remove ${member.name}?`}
                                        description="They lose access to this organization right away. You can invite them again later."
                                        confirmLabel="Remove"
                                        trigger={
                                            <Button size="sm" variant="ghost">
                                                Remove
                                            </Button>
                                        }
                                        form={destroy.form(member.id)}
                                    />
                                ),
                        },
                    ]}
                />
            </section>
        </>
    );
}
