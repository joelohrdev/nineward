<?php

use App\Enums\OrganizationRole;
use App\Models\Guardian;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;

test('downloads the roster as a CSV with guardians, in jersey order', function () {
    $organization = Organization::factory()->create();
    $season = Season::factory()->for($organization)->create(['name' => 'Spring 2026', 'age_cutoff_on' => '2026-05-01']);
    $team = Team::factory()->for($season)->create(['name' => '10U Red']);
    $caleb = Player::factory()->for($organization)->create(['first_name' => 'Caleb', 'last_name' => 'Ortiz', 'date_of_birth' => '2016-03-14']);
    $caleb->guardians()->attach(Guardian::factory()->for($organization)->create(['first_name' => 'Maria', 'last_name' => 'Ortiz', 'email' => 'maria@example.com', 'phone' => '919-555-0100']));
    rosterPlayer($team, $caleb, ['jersey_number' => 7, 'position' => 'C', 'is_captain' => true]);
    rosterPlayer($team, Player::factory()->for($organization)->create(['first_name' => 'Owen', 'last_name' => 'Park']), ['jersey_number' => 2]);

    $response = $this->actingAs($organization->owner)->get(route('teams.roster-export.show', $team));

    $response->assertOk()->assertDownload('spring-2026-10u-red-roster.csv');
    $rows = array_map(str_getcsv(...), array_filter(explode("\n", $response->streamedContent())));
    expect($rows[0][0])->toBe('Jersey')
        ->and($rows[1][2])->toBe('Park')
        ->and($rows[2])->toBe(['7', 'Caleb', 'Ortiz', '2016-03-14', '10', $caleb->bats->value, $caleb->throws->value, 'C', 'Yes', 'Maria Ortiz', 'maria@example.com', '919-555-0100']);
});

test('cells that look like spreadsheet formulas are neutralized', function () {
    $organization = Organization::factory()->create();
    $team = Team::factory()->for(Season::factory()->for($organization))->create();
    rosterPlayer($team, Player::factory()->for($organization)->create(['first_name' => '=HYPERLINK("http://evil")']));

    $content = $this->actingAs($organization->owner)->get(route('teams.roster-export.show', $team))->streamedContent();

    expect($content)->toContain("'=HYPERLINK");
});

test('only people who manage the roster can export it', function () {
    $organization = Organization::factory()->create();
    $team = Team::factory()->for(Season::factory()->for($organization))->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();

    $this->actingAs($coach)->get(route('teams.roster-export.show', $team))->assertForbidden();
});
