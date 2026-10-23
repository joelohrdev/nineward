import { Form, Head, usePage } from '@inertiajs/react';
import BillingCheckoutController from '@/actions/App/Http/Controllers/BillingCheckoutController';
import BillingPortalController from '@/actions/App/Http/Controllers/BillingPortalController';
import { PageHeader } from '@/components/scorebook/page-header';
import { SectionHeading } from '@/components/scorebook/section-heading';
import { StatusMark } from '@/components/scorebook/status-mark';
import type { StatusTone } from '@/components/scorebook/status-mark';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { daysUntil, formatDate } from '@/lib/format';

type BillingStatus =
    | 'trial'
    | 'active'
    | 'cancelling'
    | 'payment_needed'
    | 'ended';

type Props = {
    billing: {
        status: BillingStatus;
        trialEndsAt: string | null;
        endsAt: string | null;
        hasStripeCustomer: boolean;
        cardLastFour: string | null;
    };
    canManageBilling: boolean;
};

const statusLabels: Record<BillingStatus, { label: string; tone: StatusTone }> =
    {
        trial: { label: 'Free trial', tone: 'open' },
        active: { label: 'Subscribed', tone: 'done' },
        cancelling: { label: 'Cancelling', tone: 'flagged' },
        payment_needed: { label: 'Payment needed', tone: 'failed' },
        ended: { label: 'Ended', tone: 'failed' },
    };

function describe(billing: Props['billing']): string {
    switch (billing.status) {
        case 'trial': {
            const days = daysUntil(billing.trialEndsAt);

            return `Your free trial ends ${formatDate(billing.trialEndsAt)}, ${days} ${days === 1 ? 'day' : 'days'} from now. Subscribe any time to keep going without a break.`;
        }
        case 'active':
            return 'Your subscription is active. Manage your card, invoices and cancellation in the billing portal.';
        case 'cancelling':
            return `Your subscription is cancelled and stays active until ${formatDate(billing.endsAt)}. You can resume it from the billing portal before then.`;
        case 'payment_needed':
            return 'Your last payment needs attention. Update your card in the billing portal to keep access.';
        case 'ended':
            return 'Your trial or subscription has ended. Subscribe to get back to your teams and players. Nothing has been deleted.';
    }
}

export default function Billing({ billing, canManageBilling }: Props) {
    const { url } = usePage();
    const justCheckedOut = url.includes('checkout=complete');
    const status = statusLabels[billing.status];
    const canSubscribe =
        billing.status === 'trial' || billing.status === 'ended';

    return (
        <>
            <Head title="Billing" />

            <PageHeader
                title="Billing"
                context="One flat monthly price for your whole organization"
            />

            <div className="max-w-2xl space-y-10">
                {justCheckedOut && billing.status !== 'active' && (
                    <p className="border-l-4 border-grass bg-sheet px-3 py-2 text-sm font-medium">
                        Thanks. Stripe is confirming your subscription, which
                        usually takes a few seconds. Refresh this page if it
                        doesn't update.
                    </p>
                )}

                <section className="space-y-4">
                    <SectionHeading title="Status" />
                    <StatusMark tone={status.tone}>{status.label}</StatusMark>
                    <p className="text-ink-muted">{describe(billing)}</p>
                    {billing.cardLastFour && (
                        <p className="text-sm text-ink-muted">
                            Card on file ending in {billing.cardLastFour}.
                        </p>
                    )}
                </section>

                {canManageBilling ? (
                    <section className="flex flex-wrap gap-3">
                        {canSubscribe && (
                            <Form {...BillingCheckoutController.store.form()}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        size="lg"
                                        disabled={processing}
                                    >
                                        {processing && <Spinner />}
                                        Subscribe
                                    </Button>
                                )}
                            </Form>
                        )}
                        {billing.hasStripeCustomer && (
                            <Form {...BillingPortalController.store.form()}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        size="lg"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        {processing && <Spinner />}
                                        Open billing portal
                                    </Button>
                                )}
                            </Form>
                        )}
                    </section>
                ) : (
                    <p className="border-l-4 border-rule-strong bg-sheet px-3 py-2 text-sm">
                        Only the organization's owner can change billing.
                    </p>
                )}
            </div>
        </>
    );
}
