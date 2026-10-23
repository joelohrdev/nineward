<?php

use App\Enums\OrganizationRole;
use App\Models\Division;
use App\Models\Guardian;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

describe('index', function () {
    test('lists the season\'s teams by division with player counts', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create();
        $older = Division::factory()->for($organization)->create(['name' => '12U', 'display_order' => 2]);
        $younger = Division::factory()->for($organization)->create(['name' => '10U', 'display_order' => 1]);
        $twelve = Team::factory()->for($season)->create(['division_id' => $older->id, 'name' => 'Red']);
        Team::factory()->for($season)->create(['division_id' => $younger->id, 'name' => 'Blue']);
        rosterPlayer($twelve, Player::factory()->for($organization)->create());
        Player::factory()->for($organization)->create();

        $this->actingAs($organization->owner)
            ->get(route('seasons.teams.index', $season))
            ->assertInertia(fn (Assert $page) => $page
                ->component('teams/index')
                ->has('teams', 2)
                ->where('teams.0.divisionName', '10U')
                ->where('teams.1.playersCount', 1)
                ->where('unassignedCount', 1)
                ->where('canManageTeams', true));
    });

    test('coaches only see the teams they head', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create();
        $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();
        $theirs = Team::factory()->for($season)->create(['head_coach_id' => $coach->id]);
        Team::factory()->for($season)->create();

        $this->actingAs($coach)
            ->get(route('seasons.teams.index', $season))
            ->assertInertia(fn (Assert $page) => $page
                ->has('teams', 1)
                ->where('teams.0.id', $theirs->id)
                ->where('canManageTeams', false));
    });
});

describe('store', function () {
    test('creates a team in the season with a slug from its name', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create();
        $division = Division::factory()->for($organization)->create();
        $coach = User::factory()->memberOf($organization)->create();

        $this->actingAs($organization->owner)
            ->post(route('seasons.teams.store', $season), [
                'name' => '10U Red',
                'division_id' => $division->id,
                'head_coach_id' => $coach->id,
            ])
            ->assertRedirect(route('seasons.teams.show', [$season, '10u-red']));

        $team = Team::withoutGlobalScopes()->sole();
        expect($team->season_id)->toBe($season->id)
            ->and($team->organization_id)->toBe($organization->id)
            ->and($team->head_coach_id)->toBe($coach->id);
    });

    test('rejects a name already used this season, but allows it in another season', function () {
        $organization = Organization::factory()->create();
        $division = Division::factory()->for($organization)->create();
        $spring = Season::factory()->for($organization)->create();
        $fall = Season::factory()->for($organization)->create();
        Team::factory()->for($spring)->create(['name' => '10U Red', 'slug' => '10u-red', 'division_id' => $division->id]);

        $this->actingAs($organization->owner)
            ->post(route('seasons.teams.store', $spring), ['name' => '10u red', 'division_id' => $division->id])
            ->assertSessionHasErrors(['name' => 'There is already a team with that name this season.']);

        $this->actingAs($organization->owner)
            ->post(route('seasons.teams.store', $fall), ['name' => '10U Red', 'division_id' => $division->id])
            ->assertSessionHasNoErrors();
    });

    test('the division and head coach must belong to the organization', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create();

        $this->actingAs($organization->owner)
            ->post(route('seasons.teams.store', $season), [
                'name' => 'Red',
                'division_id' => Division::factory()->create()->id,
                'head_coach_id' => User::factory()->create()->id,
            ])
            ->assertSessionHasErrors(['division_id', 'head_coach_id' => 'The head coach has to be a member of your organization.']);
    });

    test('coaches cannot create teams', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create();
        $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();

        $this->actingAs($coach)
            ->post(route('seasons.teams.store', $season), ['name' => 'Red', 'division_id' => Division::factory()->for($organization)->create()->id])
            ->assertForbidden();
    });
});

describe('show', function () {
    test('shows the roster and the players still available this season', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create(['age_cutoff_on' => '2026-05-01']);
        $division = Division::factory()->for($organization)->create(['name' => '10U', 'max_age' => 10]);
        $team = Team::factory()->for($season)->create(['division_id' => $division->id]);
        $rostered = Player::factory()->for($organization)->create();
        rosterPlayer($team, $rostered, ['jersey_number' => 7]);
        $tooOld = Player::factory()->for($organization)->create(['date_of_birth' => '2013-01-01']);
        $otherTeam = Team::factory()->for($season)->create(['division_id' => $division->id]);
        rosterPlayer($otherTeam, Player::factory()->for($organization)->create());

        $this->actingAs($organization->owner)
            ->get(route('seasons.teams.show', [$season, $team]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('teams/show')
                ->has('roster', 1)
                ->where('roster.0.playerId', $rostered->id)
                ->where('roster.0.jerseyNumber', 7)
                ->has('pool', 1)
                ->where('pool.0.id', $tooOld->id)
                ->where('pool.0.fitsDivision', false)
                ->where('canManageRoster', true));
    });

    test('loads the roster without a query per player', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create();
        $team = Team::factory()->for($season)->create();
        foreach (range(1, 5) as $number) {
            rosterPlayer($team, Player::factory()->for($organization)->hasAttached(Guardian::factory()->for($organization))->create());
            Player::factory()->for($organization)->create();
        }

        $this->actingAs($organization->owner)->get(route('seasons.teams.show', [$season, $team]))->assertOk();

        DB::enableQueryLog();
        $this->actingAs($organization->owner)->get(route('seasons.teams.show', [$season, $team]))->assertOk();
        $fiveRostered = count(DB::getQueryLog());

        foreach (range(1, 5) as $number) {
            rosterPlayer($team, Player::factory()->for($organization)->hasAttached(Guardian::factory()->for($organization))->create());
        }
        DB::flushQueryLog();
        $this->actingAs($organization->owner)->get(route('seasons.teams.show', [$season, $team]))->assertOk();

        expect(count(DB::getQueryLog()))->toBe($fiveRostered);
    });

    test('a coach can open a team they head but not another team', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create();
        $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();
        $theirs = Team::factory()->for($season)->create(['head_coach_id' => $coach->id]);
        $other = Team::factory()->for($season)->create();

        $this->actingAs($coach)->get(route('seasons.teams.show', [$season, $theirs]))->assertOk();
        $this->actingAs($coach)->get(route('seasons.teams.show', [$season, $other]))->assertForbidden();
    });

    test('a team is only found under its own season', function () {
        $organization = Organization::factory()->create();
        $team = Team::factory()->for(Season::factory()->for($organization))->create();
        $otherSeason = Season::factory()->for($organization)->create();

        $this->actingAs($organization->owner)
            ->get(route('seasons.teams.show', [$otherSeason, $team]))
            ->assertNotFound();
    });
});

describe('update and destroy', function () {
    test('renaming a team moves it to its new URL', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create();
        $team = Team::factory()->for($season)->create();

        $this->actingAs($organization->owner)
            ->put(route('seasons.teams.update', [$season, $team]), ['name' => '10U Gold', 'division_id' => $team->division_id])
            ->assertRedirect(route('seasons.teams.show', [$season, '10u-gold']));
    });

    test('an empty team can be deleted', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create();
        $team = Team::factory()->for($season)->create();

        $this->actingAs($organization->owner)
            ->delete(route('seasons.teams.destroy', [$season, $team]))
            ->assertRedirect(route('seasons.teams.index', $season));

        expect(Team::withoutGlobalScopes()->onlyTrashed()->count())->toBe(1);
    });

    test('a team with players cannot be deleted', function () {
        [$team, $player] = teamAndPlayer();
        rosterPlayer($team, $player);
        $season = Season::withoutGlobalScopes()->find($team->season_id);

        $this->actingAs(Organization::find($team->organization_id)->owner)
            ->delete(route('seasons.teams.destroy', [$season, $team]))
            ->assertSessionHasErrors(['team' => 'Move or remove the players on this team before deleting it.']);

        expect(Team::withoutGlobalScopes()->onlyTrashed()->count())->toBe(0);
    });
});
