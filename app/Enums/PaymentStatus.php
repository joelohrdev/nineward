<?php

namespace App\Enums;

enum PaymentStatus: string
{
    /** Waiting for the family to finish Stripe Checkout. */
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    /** The family left Checkout without paying and the session timed out. */
    case Expired = 'expired';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Paid => 'Paid',
            self::Failed => 'Payment failed',
            self::Expired => 'Unpaid',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Whether the family still owes the fee and can be sent a link to pay.
     */
    public function isOutstanding(): bool
    {
        return in_array($this, [self::Pending, self::Failed, self::Expired], true);
    }
}
