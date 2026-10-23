import { Head, Link } from '@inertiajs/react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { PageHeader } from '@/components/scorebook/page-header';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Button } from '@/components/ui/button';
import { formatDate, formatMoney } from '@/lib/format';
import { paymentTones } from '@/lib/payment-status';
import type { PaymentSummary } from '@/lib/payment-status';
import { submissionTones } from '@/lib/submission-status';
import { show as showPlayer } from '@/routes/players';
import { index } from '@/routes/submissions';
import { store as dismiss } from '@/routes/submissions/dismissal';
import { store as review } from '@/routes/submissions/review';

type Props = {
    submission: {
        id: number;
        formTitle: string;
        formVersion: number;
        status: 'processed' | 'needs_review' | 'dismissed';
        statusLabel: string;
        reviewReason: string | null;
        submittedAt: string | null;
        reviewedBy: string | null;
        reviewedAt: string | null;
        player: { id: number; name: string; isRemoved: boolean } | null;
        guardianName: string | null;
        possibleDuplicate: {
            id: number;
            name: string;
            dateOfBirth: string;
            guardians: string[];
        } | null;
    };
    answers: { label: string; answer: string | null; isSection: boolean }[];
    payment:
        | (PaymentSummary & {
              refundedCents: number;
              disputeStatus: string | null;
              paidAt: string | null;
              stripeUrl: string | null;
              payUrl: string | null;
          })
        | null;
};

export default function ShowSubmission({
    submission,
    answers,
    payment,
}: Props) {
    const { player, possibleDuplicate } = submission;

    return (
        <>
            <Head
                title={`Registration · ${player?.name ?? 'Removed player'}`}
            />

            <PageHeader
                title={player?.name ?? 'Removed player'}
                context={
                    <span className="inline-flex flex-wrap items-center gap-x-3 gap-y-1">
                        <StatusMark tone={submissionTones[submission.status]}>
                            {submission.statusLabel}
                        </StatusMark>
                        <span>
                            {submission.formTitle} · version{' '}
                            {submission.formVersion} ·{' '}
                            {formatDate(submission.submittedAt)}
                        </span>
                    </span>
                }
                actions={
                    <>
                        {player !== null && !player.isRemoved && (
                            <Button asChild variant="outline">
                                <Link href={showPlayer(player.id)}>
                                    Player profile
                                </Link>
                            </Button>
                        )}
                        {submission.status !== 'dismissed' && (
                            <ConfirmDialog
                                title="Dismiss this registration?"
                                description="Use this for spam or test entries. The player it created stays in your list until you remove them."
                                confirmLabel="Dismiss"
                                trigger={
                                    <Button variant="ghost">Dismiss</Button>
                                }
                                form={dismiss.form(submission.id)}
                            />
                        )}
                    </>
                }
            />

            <div className="grid gap-12 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]">
                <section className="space-y-4">
                    <SectionHeading
                        title="Answers"
                        description="Exactly as the family saw and answered the form."
                    />
                    <dl>
                        {answers.map((item, position) =>
                            item.isSection ? (
                                <dt
                                    key={position}
                                    className="mt-6 border-b-2 border-ink pb-1 font-display text-xl uppercase"
                                >
                                    {item.label}
                                </dt>
                            ) : (
                                <div
                                    key={position}
                                    className="border-b border-rule py-3"
                                >
                                    <dt className="text-sm font-semibold text-ink-muted">
                                        {item.label}
                                    </dt>
                                    <dd className="mt-1 whitespace-pre-line">
                                        {item.answer ?? (
                                            <span className="text-ink-muted">
                                                No answer
                                            </span>
                                        )}
                                    </dd>
                                </div>
                            ),
                        )}
                    </dl>
                </section>

                <aside className="space-y-6">
                    {payment !== null && (
                        <section className="space-y-3 border-2 border-ink bg-sheet p-4">
                            <div className="flex items-baseline justify-between gap-3">
                                <h2 className="label-caps text-ink">
                                    Registration fee
                                </h2>
                                <span className="font-display text-2xl">
                                    {formatMoney(payment.amountCents)}
                                </span>
                            </div>
                            <StatusMark tone={paymentTones[payment.status]}>
                                {payment.statusLabel}
                            </StatusMark>
                            {payment.paidAt && (
                                <p className="text-sm text-ink-muted">
                                    Paid {formatDate(payment.paidAt)}
                                </p>
                            )}
                            {payment.refundedCents > 0 && (
                                <p className="text-sm">
                                    {formatMoney(payment.refundedCents)}{' '}
                                    refunded
                                </p>
                            )}
                            {payment.disputeStatus && (
                                <p className="border-l-4 border-danger pl-2 text-sm">
                                    Disputed:{' '}
                                    {payment.disputeStatus.replaceAll('_', ' ')}
                                </p>
                            )}
                            {payment.payUrl && (
                                <div className="space-y-2 text-sm text-ink-muted">
                                    <p>
                                        The family's confirmation email has a
                                        link to pay. You can also send them this
                                        one.
                                    </p>
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        onClick={() =>
                                            void navigator.clipboard.writeText(
                                                payment.payUrl ?? '',
                                            )
                                        }
                                    >
                                        Copy payment link
                                    </Button>
                                </div>
                            )}
                            {payment.stripeUrl && (
                                <Button asChild variant="outline" size="sm">
                                    <a
                                        href={payment.stripeUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Refund or view in Stripe
                                    </a>
                                </Button>
                            )}
                        </section>
                    )}
                    {submission.status === 'needs_review' &&
                        possibleDuplicate !== null &&
                        player !== null && (
                            <section className="space-y-4 border-2 border-warn bg-sheet p-4">
                                <h2 className="label-caps text-ink">
                                    Possible duplicate
                                </h2>
                                <p className="text-sm">
                                    {submission.reviewReason}
                                </p>
                                <div className="grid gap-1 border-l-4 border-rule-strong pl-3 text-sm">
                                    <Link
                                        href={showPlayer(possibleDuplicate.id)}
                                        className="font-semibold underline underline-offset-4"
                                    >
                                        {possibleDuplicate.name}
                                    </Link>
                                    <span className="text-ink-muted">
                                        Born{' '}
                                        {formatDate(
                                            possibleDuplicate.dateOfBirth,
                                        )}
                                    </span>
                                    <span className="text-ink-muted">
                                        Guardians:{' '}
                                        {possibleDuplicate.guardians.join(
                                            ', ',
                                        ) || 'none'}
                                    </span>
                                </div>
                                <p className="text-sm text-ink-muted">
                                    Is this the same kid{' '}
                                    {submission.guardianName} registered?
                                </p>
                                <div className="grid gap-2">
                                    <ConfirmDialog
                                        title="Same player?"
                                        description={`${submission.guardianName} is added as a guardian of ${possibleDuplicate.name}, and the copy this registration created is removed.`}
                                        confirmLabel="Yes, same player"
                                        trigger={
                                            <Button>
                                                Same player, combine them
                                            </Button>
                                        }
                                        form={review.form(submission.id, {
                                            query: {
                                                resolution: 'same_player',
                                            },
                                        })}
                                    />
                                    <Button asChild variant="outline">
                                        <Link
                                            href={review(submission.id)}
                                            data={{
                                                resolution: 'different_player',
                                            }}
                                            as="button"
                                        >
                                            Different players, keep both
                                        </Link>
                                    </Button>
                                </div>
                            </section>
                        )}

                    {submission.reviewedBy && (
                        <p className="text-sm text-ink-muted">
                            Reviewed by {submission.reviewedBy} on{' '}
                            {formatDate(submission.reviewedAt)}.
                        </p>
                    )}
                </aside>
            </div>

            <p className="mt-10 text-sm">
                <Link href={index()} className="underline underline-offset-4">
                    All registrations
                </Link>
            </p>
        </>
    );
}
