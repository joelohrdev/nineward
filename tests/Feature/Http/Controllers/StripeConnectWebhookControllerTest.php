<?php

use App\Enums\OrganizationRole;
use App\Enums\PaymentStatus;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\StripeAccountDisconnected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

beforeEach(fn () => config(['services.stripe.connect_webhook_secret' => 'whsec_test']));

/**
 * POST an event to the Connect webhook with a valid Stripe signature.
 *
 * @param  array<string, mixed>  $object
 */
function sendConnectEvent(string $type, array $object, string $account, string $eventId = 'evt_1', string $secret = 'whsec_test'): TestResponse
{
    $payload = json_encode([
        'id' => $eventId,
        'object' => 'event',
        'type' => $type,
        'account' => $account,
        'data' => ['object' => $object],
    ], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    return test()->call('POST', route('stripe.connect-webhook'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

function paymentFor(Organization $organization, array $attributes = []): Payment
{
    $submission = Submission::factory()->create(['form_id' => publishedRegistrationForm($organization)->id]);

    return Payment::factory()->create(['submission_id' => $submission->id, ...$attributes]);
}

test('rejects events without a valid signature', function () {
    $organization = Organization::factory()->create();
    $account = connectStripe($organization);
    $payment = paymentFor($organization);

    sendConnectEvent('checkout.session.completed', [
        'payment_status' => 'paid',
        'metadata' => ['payment_id' => (string) $payment->id],
    ], $account->stripe_account_id, secret: 'whsec_wrong')->assertStatus(400);

    expect(Payment::withoutGlobalScopes()->find($payment->id)->status)->toBe(PaymentStatus::Pending);
});

test('a completed checkout marks the payment paid', function () {
    $organization = Organization::factory()->create();
    $account = connectStripe($organization);
    $payment = paymentFor($organization);

    sendConnectEvent('checkout.session.completed', [
        'id' => $payment->stripe_checkout_session_id,
        'payment_status' => 'paid',
        'payment_intent' => 'pi_123',
        'metadata' => ['payment_id' => (string) $payment->id],
    ], $account->stripe_account_id)->assertOk();

    $payment = Payment::withoutGlobalScopes()->find($payment->id);
    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->stripe_payment_intent_id)->toBe('pi_123')
        ->and($payment->paid_at)->not->toBeNull();
});

test('a redelivered event changes nothing the second time', function () {
    $organization = Organization::factory()->create();
    $account = connectStripe($organization);
    $payment = paymentFor($organization, ['status' => PaymentStatus::Paid, 'stripe_payment_intent_id' => 'pi_123', 'amount_cents' => 10000]);

    $refund = ['payment_intent' => 'pi_123', 'amount_refunded' => 4000];
    sendConnectEvent('charge.refunded', $refund, $account->stripe_account_id, 'evt_refund')->assertOk();
    Payment::withoutGlobalScopes()->whereKey($payment->id)->update(['refunded_cents' => 0]);
    sendConnectEvent('charge.refunded', $refund, $account->stripe_account_id, 'evt_refund')->assertOk();

    expect(Payment::withoutGlobalScopes()->find($payment->id)->refunded_cents)->toBe(0)
        ->and(DB::table('processed_stripe_events')->count())->toBe(1);
});

test('refunds from the Stripe Dashboard update the payment', function (int $refunded, PaymentStatus $status) {
    $organization = Organization::factory()->create();
    $account = connectStripe($organization);
    $payment = paymentFor($organization, ['status' => PaymentStatus::Paid, 'stripe_payment_intent_id' => 'pi_123', 'amount_cents' => 10000]);

    sendConnectEvent('charge.refunded', ['payment_intent' => 'pi_123', 'amount_refunded' => $refunded], $account->stripe_account_id);

    $payment = Payment::withoutGlobalScopes()->find($payment->id);
    expect($payment->refunded_cents)->toBe($refunded)->and($payment->status)->toBe($status);
})->with([
    'partial refund' => [4000, PaymentStatus::Paid],
    'full refund' => [10000, PaymentStatus::Refunded],
]);

test('failed and abandoned checkouts are recorded', function (string $type, PaymentStatus $status) {
    $organization = Organization::factory()->create();
    $account = connectStripe($organization);
    $payment = paymentFor($organization);

    sendConnectEvent($type, [
        'id' => $payment->stripe_checkout_session_id,
        'metadata' => ['payment_id' => (string) $payment->id],
    ], $account->stripe_account_id);

    expect(Payment::withoutGlobalScopes()->find($payment->id)->status)->toBe($status);
})->with([
    'payment failed' => ['payment_intent.payment_failed', PaymentStatus::Failed],
    'checkout expired' => ['checkout.session.expired', PaymentStatus::Expired],
]);

test('disputes are recorded on the payment', function () {
    $organization = Organization::factory()->create();
    $account = connectStripe($organization);
    $payment = paymentFor($organization, ['status' => PaymentStatus::Paid, 'stripe_payment_intent_id' => 'pi_123']);

    sendConnectEvent('charge.dispute.created', ['payment_intent' => 'pi_123', 'status' => 'needs_response'], $account->stripe_account_id);

    expect(Payment::withoutGlobalScopes()->find($payment->id)->dispute_status)->toBe('needs_response');
});

test('an event can only touch payments of the account that sent it', function () {
    $organization = Organization::factory()->create();
    $payment = paymentFor($organization);
    $otherAccount = connectStripe(Organization::factory()->create());

    sendConnectEvent('checkout.session.completed', [
        'payment_status' => 'paid',
        'metadata' => ['payment_id' => (string) $payment->id],
    ], $otherAccount->stripe_account_id)->assertOk();

    expect(Payment::withoutGlobalScopes()->find($payment->id)->status)->toBe(PaymentStatus::Pending);
});

test('account updates and disconnects follow Stripe', function () {
    Notification::fake();
    $organization = Organization::factory()->create();
    $admin = User::factory()->memberOf($organization, OrganizationRole::Admin)->create();
    $account = connectStripe($organization, ['charges_enabled' => false]);

    sendConnectEvent('account.updated', ['charges_enabled' => true, 'payouts_enabled' => true, 'details_submitted' => true], $account->stripe_account_id, 'evt_a');
    expect($account->refresh()->charges_enabled)->toBeTrue();

    sendConnectEvent('account.application.deauthorized', [], $account->stripe_account_id, 'evt_b');
    expect($account->refresh()->disconnected_at)->not->toBeNull()
        ->and($organization->refresh()->canAcceptPayments())->toBeFalse();

    Notification::assertSentTo($organization->owner, StripeAccountDisconnected::class);
    Notification::assertNotSentTo($admin, StripeAccountDisconnected::class);
});
