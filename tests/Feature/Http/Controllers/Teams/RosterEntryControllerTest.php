<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Player;
use App\Models\RosterEntry;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

test('adds the selected players to the team for its season', function () {
    [$team, $first] = teamAndPlayer();
    $second = Player::factory()->create(['organization_id' => $team->organization_id]);
    $owner = Organization::find($team->organization_id)->owner;

    $this->actingAs($owner)
        ->post(route('teams.roster-entries.store', $team), ['player_ids' => [$first->id, $second->id]])
        ->assertRedirect();

    expect(RosterEntry::where('team_id', $team->id)->pluck('season_id')->unique()->all())->toBe([$team->season_id])
        ->and(RosterEntry::count())->toBe(2);
});

test('rejects players from another organization', function () {
    [$team] = teamAndPlayer();
    $outsider = Player::factory()->create();

    $this->actingAs(Organization::find($team->organization_id)->owner)
        ->post(route('teams.roster-entries.store', $team), ['player_ids' => [$outsider->id]])
        ->assertSessionHasErrors(['player_ids.0' => 'One of those players is no longer in your list. Refresh and try again.']);

    expect(RosterEntry::count())->toBe(0);
});

test('rejects a player already on another team this season, naming that team', function () {
    $organization = Organization::factory()->create();
    $season = Season::factory()->for($organization)->create();
    $red = Team::factory()->for($season)->create(['name' => '10U Red']);
    $blue = Team::factory()->for($season)->create(['name' => '10U Blue']);
    $player = Player::factory()->for($organization)->create(['first_name' => 'Owen', 'last_name' => 'Park']);
    rosterPlayer($red, $player);

    $this->actingAs($organization->owner)
        ->post(route('teams.roster-entries.store', $blue), ['player_ids' => [$player->id]])
        ->assertSessionHasErrors(['player_ids' => 'Owen Park is already on 10U Red this season.']);
});

test('the database refuses a second team in the same season even without validation', function () {
    $organization = Organization::factory()->create();
    $season = Season::factory()->for($organization)->create();
    $player = Player::factory()->for($organization)->create();
    rosterPlayer(Team::factory()->for($season)->create(), $player);

    rosterPlayer(Team::factory()->for($season)->create(), $player);
})->throws(UniqueConstraintViolationException::class);

test('the database refuses a roster entry whose season differs from its team\'s', function () {
    [$team, $player] = teamAndPlayer();
    $otherSeason = Season::factory()->create(['organization_id' => $team->organization_id]);

    $team->players()->attach($player, ['season_id' => $otherSeason->id]);
})->throws(QueryException::class);

test('the same player can be on teams in different seasons', function () {
    $organization = Organization::factory()->create();
    $player = Player::factory()->for($organization)->create();
    rosterPlayer(Team::factory()->for(Season::factory()->for($organization))->create(), $player);
    $fallTeam = Team::factory()->for(Season::factory()->for($organization))->create();

    $this->actingAs($organization->owner)
        ->post(route('teams.roster-entries.store', $fallTeam), ['player_ids' => [$player->id]])
        ->assertSessionHasNoErrors();

    expect(RosterEntry::count())->toBe(2);
});

test('updates jersey number, position and captaincy', function () {
    [$team, $player] = teamAndPlayer();
    $entry = rosterPlayer($team, $player);

    $this->actingAs(Organization::find($team->organization_id)->owner)
        ->put(route('teams.roster-entries.update', [$team, $entry]), ['jersey_number' => 23, 'position' => 'SS', 'is_captain' => '1'])
        ->assertSessionHasNoErrors();

    $entry->refresh();
    expect($entry->jersey_number)->toBe(23)
        ->and($entry->position->value)->toBe('SS')
        ->and($entry->is_captain)->toBeTrue();
});

test('rejects a jersey number someone on the team already wears', function () {
    [$team, $player] = teamAndPlayer();
    rosterPlayer($team, Player::factory()->create(['organization_id' => $team->organization_id]), ['jersey_number' => 7]);
    $entry = rosterPlayer($team, $player);

    $this->actingAs(Organization::find($team->organization_id)->owner)
        ->put(route('teams.roster-entries.update', [$team, $entry]), ['jersey_number' => 7])
        ->assertSessionHasErrors(['jersey_number' => 'Someone on this team already wears that number.']);
});

test('the database refuses a duplicate jersey number on a team', function () {
    [$team, $player] = teamAndPlayer();
    rosterPlayer($team, Player::factory()->create(['organization_id' => $team->organization_id]), ['jersey_number' => 7]);

    rosterPlayer($team, $player, ['jersey_number' => 7]);
})->throws(UniqueConstraintViolationException::class);

test('any number of players can have no jersey number yet', function () {
    [$team, $player] = teamAndPlayer();
    rosterPlayer($team, Player::factory()->create(['organization_id' => $team->organization_id]));
    rosterPlayer($team, $player);

    expect(RosterEntry::whereNull('jersey_number')->count())->toBe(2);
});

test('taking a player off the team returns them to the pool', function () {
    [$team, $player] = teamAndPlayer();
    $entry = rosterPlayer($team, $player);

    $this->actingAs(Organization::find($team->organization_id)->owner)
        ->delete(route('teams.roster-entries.destroy', [$team, $entry]))
        ->assertRedirect();

    expect(RosterEntry::count())->toBe(0);
});

test('a roster entry is only found under its own team', function () {
    $organization = Organization::factory()->create();
    [$team, $player] = teamAndPlayer($organization);
    $entry = rosterPlayer($team, $player);
    $otherTeam = Team::factory()->for(Season::factory()->for($organization))->create();

    $this->actingAs($organization->owner)
        ->delete(route('teams.roster-entries.destroy', [$otherTeam, $entry]))
        ->assertNotFound();

    expect(RosterEntry::count())->toBe(1);
});

test('a coach manages the roster of a team they head, and only that team', function () {
    $organization = Organization::factory()->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();
    [$theirs, $player] = teamAndPlayer($organization);
    $theirs->update(['head_coach_id' => $coach->id]);
    $other = Team::factory()->for(Season::factory()->for($organization))->create();

    $this->actingAs($coach)
        ->post(route('teams.roster-entries.store', $theirs), ['player_ids' => [$player->id]])
        ->assertSessionHasNoErrors();

    $this->actingAs($coach)
        ->post(route('teams.roster-entries.store', $other), ['player_ids' => [Player::factory()->for($organization)->create()->id]])
        ->assertForbidden();
});

test('a coach cannot change the roster of a season that has ended, but an admin can', function () {
    $organization = Organization::factory()->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();
    $admin = User::factory()->memberOf($organization, OrganizationRole::Admin)->create();
    $team = Team::factory()->for(Season::factory()->for($organization)->ended())->create(['head_coach_id' => $coach->id]);
    $entry = rosterPlayer($team, Player::factory()->for($organization)->create());

    $this->actingAs($coach)
        ->put(route('teams.roster-entries.update', [$team, $entry]), ['jersey_number' => 4])
        ->assertForbidden();

    $this->actingAs($admin)
        ->put(route('teams.roster-entries.update', [$team, $entry]), ['jersey_number' => 4])
        ->assertSessionHasNoErrors();
});

test('a coach can still export a past season\'s roster', function () {
    $organization = Organization::factory()->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();
    $team = Team::factory()->for(Season::factory()->for($organization)->ended())->create(['head_coach_id' => $coach->id]);

    $this->actingAs($coach)->get(route('teams.roster-export.show', $team))->assertOk();
});
