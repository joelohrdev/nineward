<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('lists members and pending invitations', function () {
    $organization = Organization::factory()->create();
    User::factory()->memberOf($organization, OrganizationRole::Coach)->create();
    OrganizationInvitation::factory()->for($organization)->create();
    OrganizationInvitation::factory()->for($organization)->create(['accepted_at' => now()]);

    $this->actingAs($organization->owner)
        ->get(route('organization.members.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('organization/members')
            ->has('members', 2)
            ->has('invitations', 1));
});

test('coaches cannot manage members', function () {
    $coach = User::factory()->memberOf(Organization::factory()->create(), OrganizationRole::Coach)->create();

    $this->actingAs($coach)
        ->get(route('organization.members.index'))
        ->assertForbidden();
});

test('changes a member\'s role', function () {
    $organization = Organization::factory()->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();

    $this->actingAs($organization->owner)
        ->put(route('organization.members.update', $coach), ['role' => 'admin'])
        ->assertRedirect(route('organization.members.index'));

    expect($coach->roleIn($organization))->toBe(OrganizationRole::Admin);
});

test('nobody can be made owner and the owner\'s role cannot change', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->memberOf($organization, OrganizationRole::Admin)->create();

    $this->actingAs($admin)
        ->put(route('organization.members.update', $admin), ['role' => 'owner'])
        ->assertSessionHasErrors('role');

    $this->actingAs($admin)
        ->put(route('organization.members.update', $organization->owner), ['role' => 'coach'])
        ->assertSessionHasErrors(['role' => "The owner's role can't be changed."]);

    expect($organization->owner->roleIn($organization))->toBe(OrganizationRole::Owner);
});

test('removes a member', function () {
    $organization = Organization::factory()->create();
    $coach = User::factory()->memberOf($organization)->create();

    $this->actingAs($organization->owner)
        ->delete(route('organization.members.destroy', $coach))
        ->assertRedirect(route('organization.members.index'));

    expect($coach->roleIn($organization))->toBeNull()
        ->and($coach->refresh()->current_organization_id)->toBeNull();
});

test('the owner cannot be removed', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->memberOf($organization, OrganizationRole::Admin)->create();

    $this->actingAs($admin)
        ->delete(route('organization.members.destroy', $organization->owner))
        ->assertSessionHasErrors(['member' => "The owner can't be removed."]);

    expect($organization->owner->roleIn($organization))->toBe(OrganizationRole::Owner);
});
