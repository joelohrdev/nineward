<?php

namespace App\Actions\Payments;

use App\Contracts\RegistrationPaymentGateway;
use App\Models\Organization;
use App\Models\StripeConnectAccount;

class ConnectStripeAccount
{
    public function __construct(private RegistrationPaymentGateway $gateway) {}

    /**
     * Create the organization's Stripe account the first time, or pick up
     * where onboarding left off, and return the onboarding link.
     */
    public function handle(Organization $organization): string
    {
        $account = $organization->stripeConnectAccount;

        if ($account === null) {
            $account = new StripeConnectAccount;
            $account->organization()->associate($organization);
            $account->stripe_account_id = $this->gateway->createConnectedAccount($organization->owner->email, $organization->name);
        }

        $account->disconnected_at = null;
        $account->save();

        return $this->gateway->onboardingUrl(
            $account->stripe_account_id,
            returnUrl: route('organization.stripe-account.return'),
            refreshUrl: route('organization.stripe-account.refresh'),
        );
    }

    /**
     * Ask Stripe what the account can do now. Webhooks keep this current too;
     * this covers the moment the owner comes back from onboarding.
     */
    public function syncStatus(StripeConnectAccount $account): void
    {
        $status = $this->gateway->accountStatus($account->stripe_account_id);

        $account->forceFill([
            'charges_enabled' => $status->chargesEnabled,
            'payouts_enabled' => $status->payoutsEnabled,
            'details_submitted' => $status->detailsSubmitted,
        ])->save();
    }
}
