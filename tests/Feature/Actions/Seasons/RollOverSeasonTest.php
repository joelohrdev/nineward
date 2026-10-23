<?php

use App\Actions\Seasons\RollOverSeason;
use App\Models\Organization;
use App\Models\Player;
use App\Models\RosterEntry;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Database\UniqueConstraintViolationException;

function nextSeasonAttributes(): array
{
    return ['name' => 'Spring 2027', 'starts_on' => '2027-03-01', 'ends_on' => '2027-06-30', 'age_cutoff_on' => '2027-05-01'];
}

test('copies the chosen teams into the new season with empty rosters', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $old = Season::factory()->for($organization)->create();
    $coach = User::factory()->memberOf($organization)->create();
    $red = Team::factory()->for($old)->create(['name' => '10U Red', 'slug' => '10u-red', 'head_coach_id' => $coach->id]);
    Team::factory()->for($old)->create(['name' => '10U Blue', 'slug' => '10u-blue']);
    rosterPlayer($red, Player::factory()->for($organization)->create());

    $season = app(RollOverSeason::class)->handle($organization, nextSeasonAttributes(), collect([$red]), copyHeadCoaches: false, activate: false);

    $copied = Team::where('season_id', $season->id)->get();
    expect($copied)->toHaveCount(1)
        ->and($copied->first()->name)->toBe('10U Red')
        ->and($copied->first()->slug)->toBe('10u-red')
        ->and($copied->first()->division_id)->toBe($red->division_id)
        ->and($copied->first()->head_coach_id)->toBeNull()
        ->and(RosterEntry::where('season_id', $season->id)->count())->toBe(0)
        ->and(RosterEntry::where('season_id', $old->id)->count())->toBe(1)
        ->and(Team::where('season_id', $old->id)->count())->toBe(2);
});

test('can keep the head coaches', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $coach = User::factory()->memberOf($organization)->create();
    $red = Team::factory()->for(Season::factory()->for($organization))->create(['head_coach_id' => $coach->id]);

    $season = app(RollOverSeason::class)->handle($organization, nextSeasonAttributes(), collect([$red]), copyHeadCoaches: true, activate: false);

    expect(Team::where('season_id', $season->id)->sole()->head_coach_id)->toBe($coach->id);
});

test('activates the new season only when asked', function (bool $activate) {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $old = Season::factory()->for($organization)->create();
    $organization->forceFill(['active_season_id' => $old->id])->save();

    $season = app(RollOverSeason::class)->handle($organization, nextSeasonAttributes(), collect(), copyHeadCoaches: false, activate: $activate);

    expect($organization->refresh()->active_season_id)->toBe($activate ? $season->id : $old->id);
})->with(['activate' => true, 'keep the current one' => false]);

test('a second rollover with the same name fails without leaving teams behind', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $red = Team::factory()->for(Season::factory()->for($organization))->create();
    $rollOver = app(RollOverSeason::class);
    $rollOver->handle($organization, nextSeasonAttributes(), collect([$red]), copyHeadCoaches: false, activate: true);

    expect(fn () => $rollOver->handle($organization, nextSeasonAttributes(), collect([$red]), copyHeadCoaches: false, activate: true))
        ->toThrow(UniqueConstraintViolationException::class);

    expect(Season::where('name', 'Spring 2027')->count())->toBe(1)
        ->and(Team::count())->toBe(2);
});
