<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('shows the trial status to the owner with billing controls', function () {
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->get(route('billing.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('billing/show')
            ->where('billing.status', 'trial')
            ->where('canManageBilling', true));
});

test('shows an active subscription', function () {
    $organization = Organization::factory()->subscribed()->create();

    $this->actingAs($organization->owner)
        ->get(route('billing.show'))
        ->assertInertia(fn (Assert $page) => $page->where('billing.status', 'active'));
});

test('shows an ended trial', function () {
    $organization = Organization::factory()->trialExpired()->create();

    $this->actingAs($organization->owner)
        ->get(route('billing.show'))
        ->assertInertia(fn (Assert $page) => $page->where('billing.status', 'ended'));
});

test('admins see billing status without billing controls', function () {
    $user = User::factory()->memberOf(Organization::factory()->create(), OrganizationRole::Admin)->create();

    $this->actingAs($user)
        ->get(route('billing.show'))
        ->assertInertia(fn (Assert $page) => $page->where('canManageBilling', false));
});

test('only the owner can start checkout', function () {
    $user = User::factory()->memberOf(Organization::factory()->create(), OrganizationRole::Admin)->create();

    $this->actingAs($user)
        ->post(route('billing.checkout.store'))
        ->assertForbidden();
});

test('a subscribed organization is not sent to checkout again', function () {
    $organization = Organization::factory()->subscribed()->create();

    $this->actingAs($organization->owner)
        ->post(route('billing.checkout.store'))
        ->assertRedirect(route('billing.show'));
});

test('only the owner can open the billing portal', function () {
    $user = User::factory()->memberOf(Organization::factory()->subscribed()->create(), OrganizationRole::Admin)->create();

    $this->actingAs($user)
        ->post(route('billing.portal.store'))
        ->assertForbidden();
});

test('the billing portal is unavailable before the organization has a Stripe customer', function () {
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('billing.portal.store'))
        ->assertNotFound();
});
