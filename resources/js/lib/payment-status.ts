import type { StatusTone } from '@/components/scorebook/status-mark';

export type PaymentStatus =
    | 'pending'
    | 'paid'
    | 'failed'
    | 'expired'
    | 'refunded';

export const paymentTones: Record<PaymentStatus, StatusTone> = {
    pending: 'open',
    paid: 'done',
    failed: 'failed',
    expired: 'open',
    refunded: 'neutral',
};

export type PaymentSummary = {
    amountCents: number;
    status: PaymentStatus;
    statusLabel: string;
};
