<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;

test('downloads a printable roster PDF', function () {
    $organization = Organization::factory()->create();
    $team = Team::factory()->for(Season::factory()->for($organization)->create(['name' => 'Spring 2027']))->create(['name' => '10U Red']);
    rosterPlayer($team, Player::factory()->for($organization)->create(), ['jersey_number' => 7]);

    $response = $this->actingAs($organization->owner)->get(route('teams.roster-pdf.show', $team));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('spring-2027-10u-red-roster.pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

test('people who can\'t manage the roster can\'t print it', function () {
    $organization = Organization::factory()->create();
    $team = Team::factory()->for(Season::factory()->for($organization))->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();

    $this->actingAs($coach)->get(route('teams.roster-pdf.show', $team))->assertForbidden();
});
