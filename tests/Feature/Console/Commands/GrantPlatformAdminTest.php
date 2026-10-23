<?php

use App\Models\User;

test('grants platform admin access to an existing user', function () {
    $user = User::factory()->create(['email' => 'founder@example.com']);

    $this->artisan('platform:grant-admin', ['email' => 'founder@example.com'])
        ->expectsOutput('founder@example.com is now a platform admin.')
        ->assertSuccessful();

    expect($user->refresh()->is_platform_admin)->toBeTrue();
});

test('revokes platform admin access', function () {
    $user = User::factory()->platformAdmin()->create(['email' => 'founder@example.com']);

    $this->artisan('platform:grant-admin', ['email' => 'founder@example.com', '--revoke' => true])
        ->assertSuccessful();

    expect($user->refresh()->is_platform_admin)->toBeFalse();
});

test('fails when no user has the email address', function () {
    $this->artisan('platform:grant-admin', ['email' => 'nobody@example.com'])
        ->expectsOutput('No user has that email address.')
        ->assertFailed();
});
