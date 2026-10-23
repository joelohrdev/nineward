<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationSent;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

test('invites someone by email', function () {
    Notification::fake();
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.invitations.store'), ['email' => 'Coach@Example.com', 'role' => 'coach'])
        ->assertRedirect(route('organization.members.index'));

    $invitation = OrganizationInvitation::sole();
    expect($invitation->email)->toBe('coach@example.com')
        ->and($invitation->role)->toBe(OrganizationRole::Coach)
        ->and($invitation->invited_by)->toBe($organization->owner_id);

    Notification::assertSentTo(
        new AnonymousNotifiable,
        OrganizationInvitationSent::class,
        fn (OrganizationInvitationSent $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'coach@example.com',
    );
});

test('re-inviting refreshes the existing invitation instead of adding another', function () {
    Notification::fake();
    $this->freezeSecond();
    $organization = Organization::factory()->create();
    OrganizationInvitation::factory()->for($organization)->expired()->create(['email' => 'coach@example.com']);

    $this->actingAs($organization->owner)
        ->post(route('organization.invitations.store'), ['email' => 'coach@example.com', 'role' => 'admin']);

    $invitation = OrganizationInvitation::sole();
    expect($invitation->role)->toBe(OrganizationRole::Admin)
        ->and($invitation->expires_at->equalTo(now()->addDays(OrganizationInvitation::VALID_FOR_DAYS)))->toBeTrue();
});

test('cannot invite an existing member', function () {
    Notification::fake();
    $organization = Organization::factory()->create();
    User::factory()->memberOf($organization)->create(['email' => 'coach@example.com']);

    $this->actingAs($organization->owner)
        ->post(route('organization.invitations.store'), ['email' => 'COACH@example.com', 'role' => 'coach'])
        ->assertSessionHasErrors(['email' => 'That person is already a member.']);

    Notification::assertNothingSent();
});

test('cannot invite someone as owner', function () {
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.invitations.store'), ['email' => 'new@example.com', 'role' => 'owner'])
        ->assertSessionHasErrors(['role' => 'The selected role is invalid.']);
});

test('revokes an invitation', function () {
    $organization = Organization::factory()->create();
    $invitation = OrganizationInvitation::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->delete(route('organization.invitations.destroy', $invitation->id))
        ->assertRedirect(route('organization.members.index'));

    expect(OrganizationInvitation::count())->toBe(0);
});

test('cannot revoke another organization\'s invitation', function () {
    $organization = Organization::factory()->create();
    $theirs = OrganizationInvitation::factory()->create();

    $this->actingAs($organization->owner)
        ->delete(route('organization.invitations.destroy', $theirs->id))
        ->assertNotFound();

    expect(OrganizationInvitation::count())->toBe(1);
});
