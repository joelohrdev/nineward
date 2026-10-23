<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the user works in their chosen organization', function () {
    $first = Organization::factory()->create(['name' => 'First Baseball']);
    $chosen = Organization::factory()->create(['name' => 'Chosen Baseball']);
    $user = User::factory()->memberOf($first)->memberOf($chosen, OrganizationRole::Admin)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentOrganization.id', $chosen->id)
            ->where('currentOrganization.name', 'Chosen Baseball')
            ->where('currentOrganization.role', 'admin')
            ->has('organizations', 2));
});

test('a stored organization the user no longer belongs to is replaced by one they do', function () {
    $membership = Organization::factory()->create();
    $user = User::factory()->memberOf($membership)->create();
    $user->forceFill(['current_organization_id' => Organization::factory()->create()->id])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentOrganization.id', $membership->id)
            ->has('organizations', 1));

    expect($user->refresh()->current_organization_id)->toBe($membership->id);
});

test('a user without an organization has no current organization', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentOrganization', null)
            ->where('organizations', []));
});

test('a platform admin in a support session works in the supported organization', function () {
    $own = Organization::factory()->create();
    $supported = Organization::factory()->create();
    $admin = User::factory()->platformAdmin()->memberOf($own)->create();

    $this->actingAs($admin)
        ->withSession(['support_session' => ['organization_id' => $supported->id]])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentOrganization.id', $supported->id)
            ->where('currentOrganization.role', 'admin')
            ->where('supportSession.organizationName', $supported->name));
});

test('a support session is ignored for users who are not platform admins', function () {
    $own = Organization::factory()->create();
    $user = User::factory()->memberOf($own)->create();

    $this->actingAs($user)
        ->withSession(['support_session' => ['organization_id' => Organization::factory()->create()->id]])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentOrganization.id', $own->id)
            ->where('supportSession', null));
});
