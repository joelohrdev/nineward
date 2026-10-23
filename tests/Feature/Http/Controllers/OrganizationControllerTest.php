<?php

use App\Models\Organization;
use App\Models\User;

test('users without an organization are sent to create one', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('organizations.create'));
});

test('creating an organization opens its dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('organizations.store'), ['name' => '  Apex Baseball  '])
        ->assertRedirect(route('dashboard'));

    $organization = Organization::sole();
    expect($organization->name)->toBe('Apex Baseball')
        ->and($user->refresh()->current_organization_id)->toBe($organization->id);
});

test('an organization needs a name', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('organizations.store'), ['name' => ''])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);

    expect(Organization::count())->toBe(0);
});
