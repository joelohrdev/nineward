import { Form, Head } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { StatusMark } from '@/components/scorebook/status-mark';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { destroy, store } from '@/routes/organization/stripe-account';

type Props = {
    account: {
        stripeAccountId: string;
        chargesEnabled: boolean;
        payoutsEnabled: boolean;
        detailsSubmitted: boolean;
        isDisconnected: boolean;
    } | null;
    canManagePayments: boolean;
};

function Check({ ok, label }: { ok: boolean; label: string }) {
    return (
        <li className="flex items-center justify-between border-b border-rule py-3">
            <span>{label}</span>
            {ok ? (
                <StatusMark tone="done">Ready</StatusMark>
            ) : (
                <StatusMark tone="open">Not yet</StatusMark>
            )}
        </li>
    );
}

export default function Payments({ account, canManagePayments }: Props) {
    const isReady =
        account !== null && account.chargesEnabled && !account.isDisconnected;
    const connectLabel =
        account === null
            ? 'Connect Stripe'
            : account.isDisconnected
              ? 'Reconnect Stripe'
              : 'Finish Stripe setup';

    return (
        <>
            <Head title="Payments" />

            <section className="max-w-2xl space-y-6">
                <SectionHeading
                    title="Registration fees"
                    description="Families pay straight into your own Stripe account. Your name is on their card statement, payouts go to your bank, and you handle refunds in your Stripe Dashboard. We don't take a cut."
                />

                {account?.isDisconnected && (
                    <p className="border-l-4 border-danger bg-sheet px-3 py-2">
                        Stripe is disconnected, so forms with a fee are paused.
                        Forms without a fee keep working.
                    </p>
                )}

                {account !== null && (
                    <ul className="border-t border-rule">
                        <Check
                            ok={account.detailsSubmitted}
                            label="Business details sent to Stripe"
                        />
                        <Check
                            ok={
                                account.chargesEnabled &&
                                !account.isDisconnected
                            }
                            label="Can accept payments"
                        />
                        <Check
                            ok={account.payoutsEnabled}
                            label="Payouts to your bank"
                        />
                    </ul>
                )}

                {isReady && (
                    <p className="border-l-4 border-grass bg-sheet px-3 py-2">
                        You're ready. Give a form a registration fee in its
                        details.
                    </p>
                )}

                {canManagePayments ? (
                    <div className="flex flex-wrap gap-3">
                        {!isReady && (
                            <Form {...store.form()}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        size="lg"
                                        disabled={processing}
                                    >
                                        {processing && <Spinner />}
                                        {connectLabel}
                                    </Button>
                                )}
                            </Form>
                        )}
                        {account !== null && !account.isDisconnected && (
                            <>
                                <Button asChild variant="outline" size="lg">
                                    <a
                                        href="https://dashboard.stripe.com"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Stripe Dashboard
                                        <ExternalLink />
                                    </a>
                                </Button>
                                <ConfirmDialog
                                    title="Stop taking registration fees?"
                                    description="Forms with a fee pause until you reconnect. Your Stripe account and its money aren't affected."
                                    confirmLabel="Disconnect"
                                    trigger={
                                        <Button variant="ghost">
                                            Disconnect
                                        </Button>
                                    }
                                    form={destroy.form()}
                                />
                            </>
                        )}
                    </div>
                ) : (
                    <p className="text-sm text-ink-muted">
                        Only the organization's owner can connect Stripe.
                    </p>
                )}
            </section>
        </>
    );
}
