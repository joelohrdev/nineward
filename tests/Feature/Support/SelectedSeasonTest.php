<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Season;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function organizationWithTwoSeasons(): array
{
    $organization = Organization::factory()->create();
    $active = Season::factory()->for($organization)->create(['name' => 'Spring 2027']);
    $other = Season::factory()->for($organization)->create(['name' => 'Fall 2026']);
    $organization->forceFill(['active_season_id' => $active->id])->save();

    return [$organization, $active, $other];
}

test('picking a season keeps it selected when going back home', function () {
    [$organization, $active, $other] = organizationWithTwoSeasons();

    $this->actingAs($organization->owner)->get(route('seasons.teams.index', $other))->assertOk();

    $this->actingAs($organization->owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedSeasonId', $other->id)
            ->where('season.id', $other->id)
            ->where('season.isActive', false)
            ->where('activeSeasonName', 'Spring 2027'));
});

test('the player list follows the selected season too', function () {
    [$organization, $active, $other] = organizationWithTwoSeasons();

    $this->actingAs($organization->owner)->get(route('seasons.teams.index', $other));

    $this->actingAs($organization->owner)
        ->get(route('players.index'))
        ->assertInertia(fn (Assert $page) => $page->where('activeSeasonName', 'Fall 2026'));
});

test('without a selection, pages show the active season', function () {
    [$organization, $active] = organizationWithTwoSeasons();

    $this->actingAs($organization->owner)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedSeasonId', $active->id)
            ->where('season.isActive', true));
});

test('a selected season from another organization is ignored', function () {
    [$organization, $active] = organizationWithTwoSeasons();
    $foreign = Season::factory()->create();

    $this->actingAs($organization->owner)
        ->withSession(["selected_season.{$organization->id}" => $foreign->id])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('selectedSeasonId', $active->id));
});

test('each organization remembers its own selection', function () {
    [$first, , $firstOther] = organizationWithTwoSeasons();
    [$second, $secondActive] = organizationWithTwoSeasons();
    $user = User::factory()->memberOf($second)->memberOf($first, OrganizationRole::Admin)->create();

    $this->actingAs($user)->get(route('seasons.teams.index', $firstOther));
    $user->switchOrganization($second);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('selectedSeasonId', $secondActive->id));
});
