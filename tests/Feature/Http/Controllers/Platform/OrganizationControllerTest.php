<?php

use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('platform admins can list and search every organization', function () {
    Organization::factory()->create(['name' => 'Apex Baseball']);
    Organization::factory()->create(['name' => 'Cary Trojans']);

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->get(route('platform.organizations.index', ['search' => 'Apex']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/organizations/index')
            ->has('organizationPage.data', 1)
            ->where('organizationPage.data.0.name', 'Apex Baseball'));
});

test('platform admins can view any organization', function () {
    $organization = Organization::factory()->create();

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->get(route('platform.organizations.show', $organization))
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/organizations/show')
            ->where('organization.id', $organization->id)
            ->has('organization.members', 1));
});

test('other users cannot reach the platform area', function (string $method, string $route) {
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->{$method}(route($route, $organization))
        ->assertForbidden();
})->with([
    'organization list' => ['get', 'platform.organizations.index'],
    'organization detail' => ['get', 'platform.organizations.show'],
    'extend trial' => ['put', 'platform.organizations.trial.update'],
    'start support session' => ['post', 'platform.organizations.support-session.store'],
    'end support session' => ['delete', 'platform.support-session.destroy'],
]);
