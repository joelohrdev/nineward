<?php

namespace Tests\Fixtures;

use App\Contracts\RegistrationPaymentGateway;
use App\Payments\CheckoutSession;
use App\Payments\ConnectedAccountStatus;

/**
 * Stands in for Stripe and records what the app asked it to do.
 */
class FakeRegistrationPaymentGateway implements RegistrationPaymentGateway
{
    /** @var list<array<string, mixed>> */
    public array $checkouts = [];

    /** @var list<string> */
    public array $createdAccounts = [];

    public bool $failCheckout = false;

    public function __construct(public ConnectedAccountStatus $status = new ConnectedAccountStatus(true, true, true)) {}

    public function createConnectedAccount(string $email, string $businessName): string
    {
        $id = 'acct_fake'.(count($this->createdAccounts) + 1);
        $this->createdAccounts[] = $id;

        return $id;
    }

    public function onboardingUrl(string $accountId, string $returnUrl, string $refreshUrl): string
    {
        return "https://connect.stripe.test/onboarding/{$accountId}";
    }

    public function accountStatus(string $accountId): ConnectedAccountStatus
    {
        return $this->status;
    }

    public function createCheckout(
        string $accountId,
        int $amountCents,
        string $currency,
        string $description,
        ?string $customerEmail,
        string $successUrl,
        string $cancelUrl,
        array $metadata,
    ): CheckoutSession {
        if ($this->failCheckout) {
            throw new \RuntimeException('Stripe is unreachable.');
        }

        $this->checkouts[] = compact('accountId', 'amountCents', 'currency', 'description', 'customerEmail', 'metadata');
        $id = 'cs_test_'.count($this->checkouts);

        return new CheckoutSession($id, "https://checkout.stripe.test/{$id}");
    }
}
