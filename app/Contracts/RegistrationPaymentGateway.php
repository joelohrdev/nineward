<?php

namespace App\Contracts;

use App\Payments\CheckoutSession;
use App\Payments\ConnectedAccountStatus;

/**
 * Collects registration fees in each organization's own payment account.
 * The platform is never the merchant and takes no fee.
 */
interface RegistrationPaymentGateway
{
    /**
     * Create a connected account for an organization and return its ID.
     */
    public function createConnectedAccount(string $email, string $businessName): string;

    /**
     * A one-time link to the payment provider's onboarding for the account.
     */
    public function onboardingUrl(string $accountId, string $returnUrl, string $refreshUrl): string;

    public function accountStatus(string $accountId): ConnectedAccountStatus;

    /**
     * Start a hosted checkout on the organization's account.
     *
     * @param  array<string, string>  $metadata
     */
    public function createCheckout(
        string $accountId,
        int $amountCents,
        string $currency,
        string $description,
        ?string $customerEmail,
        string $successUrl,
        string $cancelUrl,
        array $metadata,
    ): CheckoutSession;
}
