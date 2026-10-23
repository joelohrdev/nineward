<?php

use App\Enums\OrganizationRole;
use App\Models\Division;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('pre-fills the next season one year after the latest one', function () {
    $organization = Organization::factory()->create();
    $latest = Season::factory()->for($organization)->create(['starts_on' => '2026-03-01', 'ends_on' => '2026-06-30', 'age_cutoff_on' => '2026-05-01']);

    $this->actingAs($organization->owner)
        ->get(route('organization.season-rollover.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('organization/season-rollover')
            ->where('defaults.sourceSeasonId', $latest->id)
            ->where('defaults.startsOn', '2027-03-01')
            ->where('defaults.ageCutoffOn', '2027-05-01')
            ->missing('preview')
            ->has('seasons.0.isActive'));
});

test('previews which returning players change division under the new cutoff', function () {
    $organization = Organization::factory()->create();
    $source = Season::factory()->for($organization)->create(['age_cutoff_on' => '2026-05-01']);
    $tenU = Division::factory()->for($organization)->create(['name' => '10U', 'max_age' => 10]);
    Division::factory()->for($organization)->create(['name' => '11U', 'min_age' => 11, 'max_age' => 11]);
    $team = Team::factory()->for($source)->create(['division_id' => $tenU->id]);
    rosterPlayer($team, Player::factory()->for($organization)->create(['first_name' => 'Caleb', 'last_name' => 'Ortiz', 'date_of_birth' => '2016-03-14']));
    rosterPlayer($team, Player::factory()->for($organization)->create(['first_name' => 'Late', 'last_name' => 'Birthday', 'date_of_birth' => '2016-09-01']));

    $this->actingAs($organization->owner)
        ->get(route('organization.season-rollover.create', ['source_season_id' => $source->id, 'age_cutoff_on' => '2027-05-01']))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('preview')
            ->reloadOnly('preview', fn (Assert $reload) => $reload
                ->where('preview.returningCount', 2)
                ->where('preview.movingUp', [['name' => 'Caleb Ortiz', 'from' => '10U', 'to' => '11U']])));
});

test('starts the new season with the chosen teams and opens its teams', function () {
    $organization = Organization::factory()->create();
    $old = Season::factory()->for($organization)->create();
    $red = Team::factory()->for($old)->create(['name' => 'Red']);
    Team::factory()->for($old)->create(['name' => 'Blue']);

    $response = $this->actingAs($organization->owner)->post(route('organization.season-rollover.store'), [
        'name' => 'Spring 2027',
        'starts_on' => '2027-03-01',
        'ends_on' => '2027-06-30',
        'age_cutoff_on' => '2027-05-01',
        'source_season_id' => $old->id,
        'team_ids' => [$red->id],
        'activate' => true,
    ]);

    $season = Season::withoutGlobalScopes()->where('name', 'Spring 2027')->sole();
    $response->assertRedirect(route('seasons.teams.index', $season));
    expect(Team::withoutGlobalScopes()->where('season_id', $season->id)->pluck('name')->all())->toBe(['Red'])
        ->and($organization->refresh()->active_season_id)->toBe($season->id);
});

test('only teams from the chosen season can be copied', function () {
    $organization = Organization::factory()->create();
    $source = Season::factory()->for($organization)->create();
    $elsewhere = Team::factory()->for(Season::factory()->for($organization))->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.season-rollover.store'), [
            'name' => 'Spring 2027',
            'starts_on' => '2027-03-01',
            'ends_on' => '2027-06-30',
            'age_cutoff_on' => '2027-05-01',
            'source_season_id' => $source->id,
            'team_ids' => [$elsewhere->id],
        ])
        ->assertSessionHasErrors(['team_ids.0' => 'Teams can only be copied from the season you picked.']);

    expect(Season::withoutGlobalScopes()->where('name', 'Spring 2027')->exists())->toBeFalse();
});

test('rejects a season name already in use', function () {
    $organization = Organization::factory()->create();
    Season::factory()->for($organization)->create(['name' => 'Spring 2027']);

    $this->actingAs($organization->owner)
        ->post(route('organization.season-rollover.store'), [
            'name' => 'Spring 2027',
            'starts_on' => '2027-03-01',
            'ends_on' => '2027-06-30',
            'age_cutoff_on' => '2027-05-01',
        ])
        ->assertSessionHasErrors(['name' => 'You already have a season with that name.']);
});

test('coaches cannot start a season', function () {
    $coach = User::factory()->memberOf(Organization::factory()->create(), OrganizationRole::Coach)->create();

    $this->actingAs($coach)->get(route('organization.season-rollover.create'))->assertForbidden();
});
