import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import type { PublicOrganization } from '@/layouts/public-layout';
import { formatMoney } from '@/lib/format';

type Props = {
    organization: PublicOrganization;
    formTitle: string;
    playerName: string | null;
    checkout: 'complete' | 'cancelled' | 'unavailable' | 'paid' | null;
    payment: {
        amountCents: number;
        status: string;
        payUrl: string | null;
    } | null;
};

function paymentMessage(
    checkout: Props['checkout'],
    payment: NonNullable<Props['payment']>,
): string {
    if (
        checkout === 'complete' ||
        checkout === 'paid' ||
        payment.status === 'paid'
    ) {
        return `Your ${formatMoney(payment.amountCents)} payment went through. Stripe will email you a receipt.`;
    }

    if (checkout === 'unavailable') {
        return `We couldn't open the payment page just now. Your registration is saved; pay the ${formatMoney(payment.amountCents)} fee with the button below or the link in your email.`;
    }

    return `The ${formatMoney(payment.amountCents)} registration fee isn't paid yet. Your registration is saved, and you can pay any time with the button below or the link in your email.`;
}

export default function RegistrationThanks({
    organization,
    formTitle,
    playerName,
    checkout,
    payment,
}: Props) {
    return (
        <PublicLayout organization={organization}>
            <Head title={`Registered · ${organization.name}`} />

            <div className="border-2 border-ink bg-sheet p-6">
                <p className="label-caps text-ink-muted">{formTitle}</p>
                <h1 className="mt-2 font-display text-4xl leading-none uppercase">
                    {playerName
                        ? `${playerName} is registered`
                        : 'Registration received'}
                </h1>
                <p className="mt-4 text-ink-muted">
                    {organization.name} has your registration. We emailed you a
                    confirmation, with a link to set up an account if you'd like
                    to see your players and their teams.
                </p>
                {payment !== null && (
                    <div className="mt-6 space-y-4 border-t border-rule pt-4">
                        <p>{paymentMessage(checkout, payment)}</p>
                        {payment.payUrl !== null && checkout !== 'complete' && (
                            <Button
                                asChild
                                variant="brand"
                                size="lg"
                                className="w-full"
                            >
                                <a href={payment.payUrl}>
                                    Pay {formatMoney(payment.amountCents)}
                                </a>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
