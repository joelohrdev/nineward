import { Form, Head, Link } from '@inertiajs/react';
import { DataTable } from '@/components/scorebook/data-table';
import { EmptyState } from '@/components/scorebook/empty-state';
import { PageHeader } from '@/components/scorebook/page-header';
import { Pagination } from '@/components/scorebook/pagination';
import type { Paginated } from '@/components/scorebook/pagination';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Checkbox } from '@/components/ui/checkbox';
import { formatDate, formatMoney } from '@/lib/format';
import { paymentTones } from '@/lib/payment-status';
import type { PaymentSummary } from '@/lib/payment-status';
import { submissionTones } from '@/lib/submission-status';
import { index as formsIndex } from '@/routes/forms';
import { index, show } from '@/routes/submissions';

type Row = {
    id: number;
    playerName: string | null;
    guardianName: string | null;
    formTitle: string;
    status: 'processed' | 'needs_review' | 'dismissed';
    statusLabel: string;
    submittedAt: string | null;
    payment: PaymentSummary | null;
};

type Props = {
    submissions: Paginated<Row>;
    filters: { status: string | null; form: number | null; unpaid: boolean };
    formOptions: { id: number; title: string }[];
    needsReviewCount: number;
};

export default function Submissions({
    submissions,
    filters,
    formOptions,
    needsReviewCount,
}: Props) {
    return (
        <>
            <Head title="Registrations" />

            <PageHeader
                title="Registrations"
                context={
                    needsReviewCount > 0
                        ? `${needsReviewCount} ${needsReviewCount === 1 ? 'needs' : 'need'} a look`
                        : 'Everything families have submitted'
                }
                actions={
                    <Button asChild variant="outline">
                        <Link href={formsIndex()}>Forms</Link>
                    </Button>
                }
            />

            <Form
                {...index.form()}
                options={{ preserveState: true }}
                className="mb-6 flex flex-wrap items-end gap-3"
            >
                <div className="grid gap-2">
                    <Label htmlFor="filter-status">Status</Label>
                    <NativeSelect
                        id="filter-status"
                        name="status"
                        defaultValue={filters.status ?? ''}
                    >
                        <option value="">All</option>
                        <option value="needs_review">Needs review</option>
                        <option value="processed">Received</option>
                        <option value="dismissed">Dismissed</option>
                    </NativeSelect>
                </div>
                <div className="grid min-w-48 gap-2">
                    <Label htmlFor="filter-form">Form</Label>
                    <NativeSelect
                        id="filter-form"
                        name="form"
                        defaultValue={filters.form ?? ''}
                    >
                        <option value="">All forms</option>
                        {formOptions.map((form) => (
                            <option key={form.id} value={form.id}>
                                {form.title}
                            </option>
                        ))}
                    </NativeSelect>
                </div>
                <div className="flex h-10 items-center gap-2">
                    <Checkbox
                        id="filter-unpaid"
                        name="unpaid"
                        value="1"
                        defaultChecked={filters.unpaid}
                    />
                    <Label htmlFor="filter-unpaid">Fee not paid</Label>
                </div>
                <Button type="submit" variant="secondary">
                    Filter
                </Button>
            </Form>

            {submissions.data.length === 0 ? (
                <EmptyState message="No registrations yet. Share a published form's link with families." />
            ) : (
                <div className="space-y-6">
                    <DataTable
                        caption="Registrations"
                        rows={submissions.data}
                        rowKey={(row) => row.id}
                        columns={[
                            {
                                key: 'player',
                                header: 'Player',
                                primary: true,
                                cell: (row) => (
                                    <Link
                                        href={show(row.id)}
                                        className="underline decoration-rule-strong underline-offset-4 hover:decoration-clay"
                                    >
                                        {row.playerName ?? 'Removed player'}
                                    </Link>
                                ),
                            },
                            {
                                key: 'guardian',
                                header: 'Guardian',
                                cell: (row) => row.guardianName ?? '–',
                            },
                            {
                                key: 'form',
                                header: 'Form',
                                cell: (row) => row.formTitle,
                            },
                            {
                                key: 'status',
                                header: 'Status',
                                cell: (row) => (
                                    <StatusMark
                                        tone={submissionTones[row.status]}
                                    >
                                        {row.statusLabel}
                                    </StatusMark>
                                ),
                            },
                            {
                                key: 'fee',
                                header: 'Fee',
                                cell: (row) =>
                                    row.payment === null ? (
                                        '–'
                                    ) : (
                                        <span className="inline-flex items-center gap-2">
                                            {formatMoney(
                                                row.payment.amountCents,
                                            )}
                                            <StatusMark
                                                tone={
                                                    paymentTones[
                                                        row.payment.status
                                                    ]
                                                }
                                            >
                                                {row.payment.statusLabel}
                                            </StatusMark>
                                        </span>
                                    ),
                            },
                            {
                                key: 'date',
                                header: 'Received',
                                cell: (row) => formatDate(row.submittedAt),
                            },
                        ]}
                    />
                    <Pagination page={submissions} />
                </div>
            )}
        </>
    );
}
