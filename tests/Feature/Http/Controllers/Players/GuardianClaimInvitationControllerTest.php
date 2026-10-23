<?php

use App\Enums\OrganizationRole;
use App\Models\Guardian;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\GuardianClaimInvitationSent;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

test('emails an unclaimed guardian a link to set up their account', function () {
    Notification::fake();
    $organization = Organization::factory()->create();
    $guardian = Guardian::factory()->for($organization)->create(['email' => 'maria@example.com']);

    $this->actingAs($organization->owner)
        ->post(route('guardians.claim-invitation.store', $guardian))
        ->assertRedirect();

    Notification::assertSentTo(
        new AnonymousNotifiable,
        GuardianClaimInvitationSent::class,
        fn (GuardianClaimInvitationSent $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'maria@example.com',
    );
});

test('no invite is sent to a guardian who already claimed their account', function () {
    Notification::fake();
    $organization = Organization::factory()->create();
    $guardian = Guardian::factory()->for($organization)->claimedBy(User::factory()->create())->create();

    $this->actingAs($organization->owner)
        ->post(route('guardians.claim-invitation.store', $guardian))
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('coaches cannot send account invites', function () {
    Notification::fake();
    $organization = Organization::factory()->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();

    $this->actingAs($coach)
        ->post(route('guardians.claim-invitation.store', Guardian::factory()->for($organization)->create()))
        ->assertForbidden();
});
