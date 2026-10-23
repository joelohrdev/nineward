<?php

namespace App\Payments;

/**
 * What Stripe says a connected account can do.
 */
final readonly class ConnectedAccountStatus
{
    public function __construct(
        public bool $chargesEnabled,
        public bool $payoutsEnabled,
        public bool $detailsSubmitted,
    ) {}
}
