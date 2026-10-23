<?php

use App\Enums\OrganizationRole;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

function acceptUrl(OrganizationInvitation $invitation): string
{
    return URL::temporarySignedRoute('invitations.acceptance.store', $invitation->expires_at, ['invitation' => $invitation->id]);
}

test('a guest opening the link is asked to sign in and brought back afterwards', function () {
    $invitation = OrganizationInvitation::factory()->create();

    $this->get($invitation->url())
        ->assertInertia(fn (Assert $page) => $page
            ->component('invitations/show')
            ->where('invitation.email', $invitation->email)
            ->where('signedInEmail', null))
        ->assertSessionHas('url.intended', $invitation->url());
});

test('the link does not work without a valid signature', function () {
    $invitation = OrganizationInvitation::factory()->create();

    $this->get(route('invitations.show', $invitation))->assertForbidden();
});

test('the invited user joins with the invited role', function () {
    $invitation = OrganizationInvitation::factory()->create(['email' => 'coach@example.com', 'role' => OrganizationRole::Admin]);
    $user = User::factory()->create(['email' => 'Coach@example.com']);

    $this->actingAs($user)
        ->post(acceptUrl($invitation))
        ->assertRedirect(route('dashboard'));

    expect($user->roleIn($invitation->organization))->toBe(OrganizationRole::Admin)
        ->and($user->refresh()->current_organization_id)->toBe($invitation->organization_id)
        ->and($invitation->refresh()->accepted_at)->not->toBeNull();
});

test('someone signed in with a different email cannot accept', function () {
    $invitation = OrganizationInvitation::factory()->create(['email' => 'coach@example.com']);
    $user = User::factory()->create(['email' => 'someone@example.com']);

    $this->actingAs($user)
        ->post(acceptUrl($invitation))
        ->assertSessionHasErrors(['invitation' => 'This invitation was sent to coach@example.com.']);

    expect($user->roleIn($invitation->organization))->toBeNull();
});

test('an unverified account cannot accept', function () {
    $invitation = OrganizationInvitation::factory()->create(['email' => 'coach@example.com']);
    $user = User::factory()->unverified()->create(['email' => 'coach@example.com']);

    $this->actingAs($user)
        ->post(acceptUrl($invitation))
        ->assertRedirect(route('verification.notice'));

    expect($user->roleIn($invitation->organization))->toBeNull();
});

test('an expired invitation cannot be accepted', function () {
    $invitation = OrganizationInvitation::factory()->create(['email' => 'coach@example.com']);
    $user = User::factory()->create(['email' => 'coach@example.com']);
    $url = acceptUrl($invitation);
    $invitation->forceFill(['expires_at' => now()->subMinute()])->save();

    $this->actingAs($user)
        ->post($url)
        ->assertSessionHasErrors(['invitation' => 'This invitation has expired or was already used.']);
});

test('a used invitation cannot be accepted again', function () {
    $invitation = OrganizationInvitation::factory()->create(['email' => 'coach@example.com', 'accepted_at' => now()]);

    $this->actingAs(User::factory()->create(['email' => 'coach@example.com']))
        ->post(acceptUrl($invitation))
        ->assertSessionHasErrors('invitation');
});

test('a new user without an organization is sent back to their invitation', function () {
    $invitation = OrganizationInvitation::factory()->create(['email' => 'coach@example.com']);
    $user = User::factory()->create(['email' => 'coach@example.com']);

    $this->actingAs($user)->get($invitation->url());

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect($invitation->url());
});
