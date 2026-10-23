<?php

use App\Models\Organization;
use App\Models\User;

test('members of an organization on its free trial can use the app', function () {
    $user = User::factory()->memberOf(Organization::factory()->create())->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('members of a subscribed organization can use the app after the trial', function () {
    $user = User::factory()->memberOf(Organization::factory()->subscribed()->create())->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('members of an organization whose trial ended are sent to billing', function () {
    $user = User::factory()->memberOf(Organization::factory()->trialExpired()->create())->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('billing.show'));
});

test('billing stays reachable after the trial ends', function () {
    $organization = Organization::factory()->trialExpired()->create();

    $this->actingAs($organization->owner)
        ->get(route('billing.show'))
        ->assertOk();
});

test('platform admins in a support session can use a lapsed organization', function () {
    $lapsed = Organization::factory()->trialExpired()->create();

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->withSession(['support_session' => ['organization_id' => $lapsed->id]])
        ->get(route('dashboard'))
        ->assertOk();
});
