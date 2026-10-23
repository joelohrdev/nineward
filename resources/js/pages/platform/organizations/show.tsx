import { Form, Head } from '@inertiajs/react';
import OrganizationTrialController from '@/actions/App/Http/Controllers/Platform/OrganizationTrialController';
import SupportSessionController from '@/actions/App/Http/Controllers/Platform/SupportSessionController';
import InputError from '@/components/input-error';
import { DataTable } from '@/components/scorebook/data-table';
import { PageHeader } from '@/components/scorebook/page-header';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { platformStatusLabel, platformStatusTone } from '@/lib/platform-status';
import type { PlatformStatus } from '@/lib/platform-status';

type Props = {
    organization: {
        id: number;
        name: string;
        slug: string;
        ownerName: string;
        ownerEmail: string;
        status: PlatformStatus;
        trialEndsAt: string | null;
        createdAt: string | null;
        members: { id: number; name: string; email: string; role: string }[];
    };
    auditLogs: {
        id: number;
        action: string;
        reason: string | null;
        userName: string;
        createdAt: string | null;
    }[];
};

export default function PlatformOrganization({
    organization,
    auditLogs,
}: Props) {
    return (
        <>
            <Head title={organization.name} />

            <PageHeader
                title={organization.name}
                context={`/${organization.slug} · owned by ${organization.ownerName} (${organization.ownerEmail})`}
                actions={
                    <StatusMark tone={platformStatusTone[organization.status]}>
                        {platformStatusLabel[organization.status]}
                    </StatusMark>
                }
            />

            <div className="grid gap-12 lg:grid-cols-2">
                <section className="space-y-4">
                    <SectionHeading
                        title="Support session"
                        description="See the app exactly as this organization's admins do. Entering is logged, and so is every change you make inside."
                    />
                    <Form
                        {...SupportSessionController.store.form(
                            organization.id,
                        )}
                        className="grid gap-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="support-reason">
                                        Reason
                                    </Label>
                                    <Input
                                        id="support-reason"
                                        name="reason"
                                        placeholder="Owner asked for help with roster export"
                                    />
                                    <InputError message={errors.reason} />
                                </div>
                                <div>
                                    <Button type="submit" disabled={processing}>
                                        Enter organization
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </section>

                <section className="space-y-4">
                    <SectionHeading
                        title="Free trial"
                        description={`Trial ends ${formatDate(organization.trialEndsAt)}. Extending counts from today if it already ended.`}
                    />
                    <Form
                        {...OrganizationTrialController.update.form(
                            organization.id,
                        )}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="grid gap-4 sm:grid-cols-[8rem_1fr]"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="trial-days">Days</Label>
                                    <Input
                                        id="trial-days"
                                        name="days"
                                        type="number"
                                        min={1}
                                        max={90}
                                        defaultValue={14}
                                    />
                                    <InputError message={errors.days} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="trial-reason">Reason</Label>
                                    <Input
                                        id="trial-reason"
                                        name="reason"
                                        placeholder="Season started late"
                                    />
                                    <InputError message={errors.reason} />
                                </div>
                                <div className="sm:col-span-2">
                                    <Button
                                        type="submit"
                                        variant="secondary"
                                        disabled={processing}
                                    >
                                        Extend trial
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </section>
            </div>

            <section className="mt-12 space-y-4">
                <SectionHeading title="Members" />
                <DataTable
                    caption="Members"
                    rows={organization.members}
                    rowKey={(member) => member.id}
                    columns={[
                        {
                            key: 'name',
                            header: 'Name',
                            primary: true,
                            cell: (member) => member.name,
                        },
                        {
                            key: 'email',
                            header: 'Email',
                            cell: (member) => member.email,
                        },
                        {
                            key: 'role',
                            header: 'Role',
                            cell: (member) => member.role,
                        },
                    ]}
                />
            </section>

            <section className="mt-12 space-y-4">
                <SectionHeading
                    title="Platform activity"
                    description="The last 20 things platform admins did here"
                />
                {auditLogs.length === 0 ? (
                    <p className="text-sm text-ink-muted">Nothing yet.</p>
                ) : (
                    <DataTable
                        caption="Platform activity"
                        rows={auditLogs}
                        rowKey={(log) => log.id}
                        columns={[
                            {
                                key: 'action',
                                header: 'Action',
                                primary: true,
                                cell: (log) => log.action,
                            },
                            {
                                key: 'reason',
                                header: 'Reason',
                                cell: (log) => log.reason ?? '–',
                            },
                            {
                                key: 'by',
                                header: 'By',
                                cell: (log) => log.userName,
                            },
                            {
                                key: 'when',
                                header: 'When',
                                cell: (log) => formatDate(log.createdAt),
                            },
                        ]}
                    />
                )}
            </section>
        </>
    );
}
