<?php

use App\Models\Organization;
use App\Models\Player;
use App\Models\RosterEntry;
use App\Models\Season;
use App\Models\Team;

test('moves a player to another team in the same season in one step', function () {
    $organization = Organization::factory()->create();
    $season = Season::factory()->for($organization)->create();
    $red = Team::factory()->for($season)->create();
    $blue = Team::factory()->for($season)->create();
    $entry = rosterPlayer($red, Player::factory()->for($organization)->create(), ['jersey_number' => 9, 'is_captain' => true]);

    $this->actingAs($organization->owner)
        ->post(route('teams.roster-entries.transfer.store', [$red, $entry]), ['team_id' => $blue->id])
        ->assertRedirect();

    $moved = RosterEntry::sole();
    expect($moved->team_id)->toBe($blue->id)
        ->and($moved->jersey_number)->toBeNull()
        ->and($moved->is_captain)->toBeFalse();
});

test('cannot move a player to a team in another season', function () {
    $organization = Organization::factory()->create();
    $red = Team::factory()->for(Season::factory()->for($organization))->create();
    $elsewhere = Team::factory()->for(Season::factory()->for($organization))->create();
    $entry = rosterPlayer($red, Player::factory()->for($organization)->create());

    $this->actingAs($organization->owner)
        ->post(route('teams.roster-entries.transfer.store', [$red, $entry]), ['team_id' => $elsewhere->id])
        ->assertSessionHasErrors(['team_id' => 'Players can only move to a team in the same season.']);

    expect(RosterEntry::sole()->team_id)->toBe($red->id);
});

test('cannot move a player to another organization\'s team', function () {
    $organization = Organization::factory()->create();
    $red = Team::factory()->for(Season::factory()->for($organization))->create();
    $entry = rosterPlayer($red, Player::factory()->for($organization)->create());

    $this->actingAs($organization->owner)
        ->post(route('teams.roster-entries.transfer.store', [$red, $entry]), ['team_id' => Team::factory()->create()->id])
        ->assertSessionHasErrors('team_id');

    expect(RosterEntry::sole()->team_id)->toBe($red->id);
});
