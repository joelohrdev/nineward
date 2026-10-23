<?php

use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('members of an organization can visit the dashboard', function () {
    $user = User::factory()->memberOf(Organization::factory()->create())->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
