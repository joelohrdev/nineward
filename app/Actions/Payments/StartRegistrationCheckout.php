<?php

namespace App\Actions\Payments;

use App\Contracts\RegistrationPaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Validation\ValidationException;

class StartRegistrationCheckout
{
    public function __construct(private RegistrationPaymentGateway $gateway) {}

    /**
     * Create the fee for a registration, copying the form's price as it is
     * right now, and return the Checkout URL to send the family to.
     */
    public function forNewSubmission(Organization $organization, Submission $submission): string
    {
        $payment = new Payment([]);
        $payment->forceFill([
            'organization_id' => $organization->id,
            'amount_cents' => $submission->form->price_cents,
            'currency' => config()->string('cashier.currency', 'usd'),
            'status' => PaymentStatus::Pending,
        ]);
        $payment->submission()->associate($submission);
        $payment->save();

        return $this->checkout($organization, $payment);
    }

    /**
     * Send the family to Checkout for a fee they still owe.
     */
    public function checkout(Organization $organization, Payment $payment): string
    {
        $account = $organization->stripeConnectAccount;

        if ($account === null || ! $account->canAcceptPayments()) {
            throw ValidationException::withMessages(['payment' => "{$organization->name} can't take payments right now."]);
        }

        $payment->loadMissing('submission.form', 'submission.player', 'submission.guardian');
        $submission = $payment->submission;

        $session = $this->gateway->createCheckout(
            accountId: $account->stripe_account_id,
            amountCents: $payment->amount_cents,
            currency: $payment->currency,
            description: "{$submission->form->title}: {$submission->player?->fullName()}",
            customerEmail: $submission->guardian?->email,
            successUrl: route('registration.thanks', [$organization->slug, $submission->form->slug, 'checkout' => 'complete']),
            cancelUrl: route('registration.thanks', [$organization->slug, $submission->form->slug, 'checkout' => 'cancelled']),
            metadata: ['payment_id' => (string) $payment->id],
        );

        $payment->forceFill(['stripe_checkout_session_id' => $session->id, 'status' => PaymentStatus::Pending])->save();

        return $session->url;
    }
}
