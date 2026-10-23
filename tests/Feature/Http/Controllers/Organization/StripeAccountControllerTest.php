<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\StripeConnectAccount;
use App\Models\User;
use App\Payments\ConnectedAccountStatus;

test('the owner connects a new Stripe account and is sent to onboarding', function () {
    $gateway = fakePaymentGateway();
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.stripe-account.store'))
        ->assertRedirect('https://connect.stripe.test/onboarding/acct_fake1');

    $account = StripeConnectAccount::sole();
    expect($account->organization_id)->toBe($organization->id)
        ->and($account->stripe_account_id)->toBe('acct_fake1')
        ->and($account->charges_enabled)->toBeFalse()
        ->and($gateway->createdAccounts)->toBe(['acct_fake1']);
});

test('continuing onboarding reuses the same account', function () {
    $gateway = fakePaymentGateway();
    $organization = Organization::factory()->create();
    connectStripe($organization, ['charges_enabled' => false, 'disconnected_at' => now()]);

    $this->actingAs($organization->owner)->post(route('organization.stripe-account.store'));

    expect($gateway->createdAccounts)->toBe([])
        ->and(StripeConnectAccount::sole()->disconnected_at)->toBeNull();
});

test('coming back from onboarding syncs what the account can do', function () {
    $gateway = fakePaymentGateway();
    $gateway->status = new ConnectedAccountStatus(chargesEnabled: true, payoutsEnabled: false, detailsSubmitted: true);
    $organization = Organization::factory()->create();
    connectStripe($organization, ['charges_enabled' => false, 'payouts_enabled' => false, 'details_submitted' => false]);

    $this->actingAs($organization->owner)
        ->get(route('organization.stripe-account.return'))
        ->assertRedirect(route('organization.payments.show'));

    $account = StripeConnectAccount::sole();
    expect($account->charges_enabled)->toBeTrue()->and($account->payouts_enabled)->toBeFalse();
});

test('the owner can stop taking fees', function () {
    $organization = Organization::factory()->create();
    connectStripe($organization);

    $this->actingAs($organization->owner)->delete(route('organization.stripe-account.destroy'));

    expect($organization->refresh()->canAcceptPayments())->toBeFalse();
});

test('only the owner can connect or disconnect Stripe', function (string $method, string $route) {
    fakePaymentGateway();
    $organization = Organization::factory()->create();
    connectStripe($organization);
    $admin = User::factory()->memberOf($organization, OrganizationRole::Admin)->create();

    $this->actingAs($admin)->{$method}(route($route))->assertForbidden();
})->with([
    'connect' => ['post', 'organization.stripe-account.store'],
    'disconnect' => ['delete', 'organization.stripe-account.destroy'],
]);
