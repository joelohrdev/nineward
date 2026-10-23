<?php

namespace App\Payments;

use App\Contracts\RegistrationPaymentGateway;
use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;

/**
 * Registration fees as Stripe direct charges on the organization's Standard
 * connected account, with no application fee.
 */
class StripeRegistrationPaymentGateway implements RegistrationPaymentGateway
{
    private ?StripeClient $client = null;

    public function __construct(private ?string $secret) {}

    /**
     * Built on first use, so pages that never talk to Stripe don't need keys.
     */
    private function stripe(): StripeClient
    {
        if ($this->client === null) {
            $httpClient = new CurlClient;
            $httpClient->setConnectTimeout(5);
            $httpClient->setTimeout(20);
            ApiRequestor::setHttpClient($httpClient);

            $this->client = new StripeClient(['api_key' => $this->secret ?: null, 'max_network_retries' => 2]);
        }

        return $this->client;
    }

    public function createConnectedAccount(string $email, string $businessName): string
    {
        return $this->stripe()->accounts->create([
            'type' => 'standard',
            'email' => $email,
            'business_profile' => ['name' => $businessName],
        ])->id;
    }

    public function onboardingUrl(string $accountId, string $returnUrl, string $refreshUrl): string
    {
        return $this->stripe()->accountLinks->create([
            'account' => $accountId,
            'type' => 'account_onboarding',
            'return_url' => $returnUrl,
            'refresh_url' => $refreshUrl,
        ])->url;
    }

    public function accountStatus(string $accountId): ConnectedAccountStatus
    {
        $account = $this->stripe()->accounts->retrieve($accountId);

        return new ConnectedAccountStatus(
            chargesEnabled: (bool) $account->charges_enabled,
            payoutsEnabled: (bool) $account->payouts_enabled,
            detailsSubmitted: (bool) $account->details_submitted,
        );
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
        $session = $this->stripe()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amountCents,
                    'product_data' => ['name' => $description],
                ],
            ]],
            'customer_email' => $customerEmail,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            'payment_intent_data' => ['metadata' => $metadata],
        ], ['stripe_account' => $accountId]);

        return new CheckoutSession($session->id, (string) $session->url);
    }
}
