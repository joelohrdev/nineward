<?php

use App\Models\Guardian;
use App\Models\Organization;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

function claimLink(Guardian $guardian): string
{
    return URL::temporarySignedRoute('guardian-claims.show', now()->addDay(), ['guardian' => $guardian->id]);
}

function claimSubmitUrl(Guardian $guardian): string
{
    return URL::temporarySignedRoute('guardian-claims.store', now()->addHour(), ['guardian' => $guardian->id]);
}

test('a parent opening the link sees the organization that invited them', function () {
    $guardian = Guardian::factory()->for(Organization::factory()->create(['name' => 'Cary Trojans']))->create();

    $this->get(claimLink($guardian))
        ->assertInertia(fn (Assert $page) => $page
            ->component('guardian-claims/show')
            ->where('claim.organizationName', 'Cary Trojans')
            ->where('claim.email', $guardian->email));
});

test('the link needs a valid signature', function () {
    $guardian = Guardian::factory()->create();

    $this->get(route('guardian-claims.show', $guardian->id))->assertForbidden();
});

test('a verified parent with the same email links the guardian to their account', function () {
    $guardian = Guardian::factory()->create(['email' => 'maria@example.com']);
    $parent = User::factory()->create(['email' => 'Maria@example.com']);

    $this->actingAs($parent)
        ->post(claimSubmitUrl($guardian))
        ->assertRedirect(route('my-players.index'));

    expect(Guardian::withoutGlobalScopes()->find($guardian->id)->user_id)->toBe($parent->id);
});

test('an account with a different email cannot claim', function () {
    $guardian = Guardian::factory()->create(['email' => 'maria@example.com']);

    $this->actingAs(User::factory()->create(['email' => 'someone@example.com']))
        ->post(claimSubmitUrl($guardian))
        ->assertSessionHasErrors(['claim' => 'This link was sent to maria@example.com.']);

    expect(Guardian::withoutGlobalScopes()->find($guardian->id)->user_id)->toBeNull();
});

test('an unverified account cannot claim', function () {
    $guardian = Guardian::factory()->create(['email' => 'maria@example.com']);

    $this->actingAs(User::factory()->unverified()->create(['email' => 'maria@example.com']))
        ->post(claimSubmitUrl($guardian))
        ->assertRedirect(route('verification.notice'));

    expect(Guardian::withoutGlobalScopes()->find($guardian->id)->user_id)->toBeNull();
});

test('a guardian claimed by another account cannot be claimed again', function () {
    // The first claimer has since changed their login email.
    $guardian = Guardian::factory()->claimedBy(User::factory()->create(['email' => 'maria.new@example.com']))->create(['email' => 'maria@example.com']);
    $other = User::factory()->create(['email' => 'maria@example.com']);

    $this->actingAs($other)
        ->post(claimSubmitUrl($guardian))
        ->assertSessionHasErrors(['claim' => 'This guardian is already linked to another account.']);
});

test('a claimed parent sees their own players across organizations and no one else\'s', function () {
    $parent = User::factory()->create();
    $first = Guardian::factory()->claimedBy($parent)->create();
    $second = Guardian::factory()->claimedBy($parent)->create(['email' => 'other-'.$parent->email]);
    $mine = Player::factory()->create(['organization_id' => $first->organization_id, 'first_name' => 'Ava']);
    $mine->guardians()->attach($first);
    $alsoMine = Player::factory()->create(['organization_id' => $second->organization_id, 'first_name' => 'Ben']);
    $alsoMine->guardians()->attach($second);
    Player::factory()->create(['organization_id' => $first->organization_id, 'first_name' => 'Cal']);

    $this->actingAs($parent)
        ->get(route('my-players.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('players', 2)
            ->where('players.0.name', $mine->fullName())
            ->where('players.1.name', $alsoMine->fullName()));
});

test('a parent without an organization lands on their players', function () {
    $parent = User::factory()->create();
    Guardian::factory()->claimedBy($parent)->create();

    $this->actingAs($parent)
        ->get(route('dashboard'))
        ->assertRedirect(route('my-players.index'));
});
