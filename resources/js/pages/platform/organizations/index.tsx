import { Form, Head, Link } from '@inertiajs/react';
import { DataTable } from '@/components/scorebook/data-table';
import { EmptyState } from '@/components/scorebook/empty-state';
import { PageHeader } from '@/components/scorebook/page-header';
import { Pagination } from '@/components/scorebook/pagination';
import type { Paginated } from '@/components/scorebook/pagination';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import { index, show } from '@/routes/platform/organizations';
import { platformStatusTone, platformStatusLabel } from '@/lib/platform-status';
import type { PlatformStatus } from '@/lib/platform-status';

type OrganizationRow = {
    id: number;
    name: string;
    slug: string;
    ownerEmail: string;
    membersCount: number;
    status: PlatformStatus;
    createdAt: string | null;
};

type Props = {
    organizationPage: Paginated<OrganizationRow>;
    search: string;
};

export default function PlatformOrganizations({
    organizationPage: organizations,
    search,
}: Props) {
    return (
        <>
            <Head title="Organizations" />

            <PageHeader
                title="Organizations"
                context={`${organizations.total} on the platform`}
            />

            <Form
                {...index.form()}
                className="mb-6 flex max-w-xl items-end gap-2"
                options={{ preserveState: true }}
            >
                <div className="grid flex-1 gap-2">
                    <Label htmlFor="search">Search</Label>
                    <Input
                        id="search"
                        name="search"
                        type="search"
                        defaultValue={search}
                        placeholder="Name, slug or owner email"
                    />
                </div>
                <Button type="submit" variant="secondary">
                    Search
                </Button>
            </Form>

            {organizations.data.length === 0 ? (
                <EmptyState message="No organizations match that search." />
            ) : (
                <DataTable
                    caption="Organizations"
                    rows={organizations.data}
                    rowKey={(organization) => organization.id}
                    columns={[
                        {
                            key: 'name',
                            header: 'Organization',
                            primary: true,
                            cell: (organization) => (
                                <Link
                                    href={show(organization.id)}
                                    className="underline decoration-rule-strong underline-offset-4 hover:decoration-clay"
                                >
                                    {organization.name}
                                </Link>
                            ),
                        },
                        {
                            key: 'owner',
                            header: 'Owner',
                            cell: (organization) => organization.ownerEmail,
                        },
                        {
                            key: 'members',
                            header: 'Members',
                            cell: (organization) => organization.membersCount,
                        },
                        {
                            key: 'status',
                            header: 'Status',
                            cell: (organization) => (
                                <StatusMark
                                    tone={
                                        platformStatusTone[organization.status]
                                    }
                                >
                                    {platformStatusLabel[organization.status]}
                                </StatusMark>
                            ),
                        },
                        {
                            key: 'created',
                            header: 'Joined',
                            cell: (organization) =>
                                formatDate(organization.createdAt),
                        },
                    ]}
                />
            )}

            <Pagination page={organizations} />
        </>
    );
}
