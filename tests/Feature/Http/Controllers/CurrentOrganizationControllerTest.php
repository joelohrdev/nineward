<?php

use App\Models\Organization;
use App\Models\User;

test('a member can switch to another of their organizations', function () {
    $current = Organization::factory()->create();
    $next = Organization::factory()->create();
    $user = User::factory()->memberOf($next)->memberOf($current)->create();

    $this->actingAs($user)
        ->put(route('current-organization.update'), ['organization_id' => $next->id])
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->current_organization_id)->toBe($next->id);
});

test('a user cannot switch to an organization they are not a member of', function () {
    $current = Organization::factory()->create();
    $user = User::factory()->memberOf($current)->create();

    $this->actingAs($user)
        ->put(route('current-organization.update'), [
            'organization_id' => Organization::factory()->create()->id,
        ])
        ->assertSessionHasErrors(['organization_id' => 'You are not a member of that organization.']);

    expect($user->refresh()->current_organization_id)->toBe($current->id);
});

test('guests are redirected to log in', function () {
    $this->put(route('current-organization.update'), [
        'organization_id' => Organization::factory()->create()->id,
    ])->assertRedirect(route('login'));
});
