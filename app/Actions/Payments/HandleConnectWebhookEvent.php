<?php

namespace App\Actions\Payments;

use App\Enums\OrganizationRole;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\StripeConnectAccount;
use App\Notifications\StripeAccountDisconnected;
use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Applies one Stripe Connect event. Each event ID is recorded in the same
 * transaction as its change, so Stripe redelivering an event changes nothing.
 */
class HandleConnectWebhookEvent
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    /**
     * @param  array<string, mixed>  $event  the decoded, signature-verified event
     */
    public function handle(array $event): void
    {
        $accountId = is_string($event['account'] ?? null) ? $event['account'] : null;
        $account = $accountId === null ? null : StripeConnectAccount::query()->where('stripe_account_id', $accountId)->first();

        if ($account === null) {
            return;
        }

        // Payments are tenant-scoped; the event names the organization by its account.
        $this->currentOrganization->set($account->organization);

        $disconnected = DB::transaction(function () use ($event, $account): bool {
            $isNew = DB::table('processed_stripe_events')->insertOrIgnore([
                'stripe_event_id' => (string) $event['id'],
                'type' => (string) $event['type'],
                'processed_at' => now(),
            ]) === 1;

            if (! $isNew) {
                return false;
            }

            $object = is_array($event['data']['object'] ?? null) ? $event['data']['object'] : [];

            return $this->apply((string) $event['type'], $object, $account);
        });

        if ($disconnected) {
            $owners = $account->organization->members()->wherePivot('role', OrganizationRole::Owner->value)->get();
            Notification::send($owners, new StripeAccountDisconnected($account->organization));
        }
    }

    /**
     * @param  array<string, mixed>  $object
     * @return bool whether the account was just disconnected
     */
    private function apply(string $type, array $object, StripeConnectAccount $account): bool
    {
        switch ($type) {
            case 'account.updated':
                $account->forceFill([
                    'charges_enabled' => (bool) ($object['charges_enabled'] ?? false),
                    'payouts_enabled' => (bool) ($object['payouts_enabled'] ?? false),
                    'details_submitted' => (bool) ($object['details_submitted'] ?? false),
                ])->save();

                return false;

            case 'account.application.deauthorized':
                $account->forceFill(['charges_enabled' => false, 'disconnected_at' => now()])->save();

                return true;

            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                if (($object['payment_status'] ?? null) === 'paid') {
                    $this->paymentFromMetadata($object)?->forceFill([
                        'status' => PaymentStatus::Paid,
                        'stripe_payment_intent_id' => $object['payment_intent'] ?? null,
                        'paid_at' => now(),
                    ])->save();
                }

                return false;

            case 'checkout.session.async_payment_failed':
            case 'payment_intent.payment_failed':
                $payment = $this->paymentFromMetadata($object);

                if ($payment !== null && $payment->status !== PaymentStatus::Paid) {
                    $payment->forceFill(['status' => PaymentStatus::Failed])->save();
                }

                return false;

            case 'checkout.session.expired':
                $payment = $this->paymentFromMetadata($object);

                if ($payment?->status === PaymentStatus::Pending && $payment->stripe_checkout_session_id === ($object['id'] ?? null)) {
                    $payment->forceFill(['status' => PaymentStatus::Expired])->save();
                }

                return false;

            case 'charge.refunded':
                $payment = $this->paymentFromIntent($object['payment_intent'] ?? null);

                if ($payment !== null) {
                    $refunded = (int) ($object['amount_refunded'] ?? 0);
                    $payment->forceFill([
                        'refunded_cents' => $refunded,
                        'status' => $refunded >= $payment->amount_cents ? PaymentStatus::Refunded : $payment->status,
                    ])->save();
                }

                return false;

            case 'charge.dispute.created':
            case 'charge.dispute.updated':
            case 'charge.dispute.closed':
                $this->paymentFromIntent($object['payment_intent'] ?? null)
                    ?->forceFill(['dispute_status' => (string) ($object['status'] ?? 'needs_response')])
                    ->save();

                return false;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $object
     */
    private function paymentFromMetadata(array $object): ?Payment
    {
        $paymentId = $object['metadata']['payment_id'] ?? null;

        return is_numeric($paymentId) ? Payment::query()->lockForUpdate()->find((int) $paymentId) : null;
    }

    private function paymentFromIntent(mixed $paymentIntentId): ?Payment
    {
        return is_string($paymentIntentId)
            ? Payment::query()->where('stripe_payment_intent_id', $paymentIntentId)->lockForUpdate()->first()
            : null;
    }
}
