<?php

use App\Enums\OrganizationRole;
use App\Models\Guardian;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\CurrentOrganization;

test('coaches see guardians of players on teams they head, including past seasons, and no others', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();
    $pastTeam = Team::factory()->for(Season::factory()->for($organization)->ended())->create(['head_coach_id' => $coach->id]);
    $theirPlayer = Player::factory()->for($organization)->create();
    $theirGuardian = Guardian::factory()->for($organization)->create();
    $theirPlayer->guardians()->attach($theirGuardian);
    rosterPlayer($pastTeam, $theirPlayer);
    $otherGuardian = Guardian::factory()->for($organization)->create();
    $otherPlayer = Player::factory()->for($organization)->create();
    $otherPlayer->guardians()->attach($otherGuardian);
    rosterPlayer(Team::factory()->for(Season::factory()->for($organization))->create(), $otherPlayer);

    expect($coach->can('view', $theirGuardian))->toBeTrue()
        ->and($coach->can('view', $otherGuardian))->toBeFalse()
        ->and($coach->can('update', $theirGuardian))->toBeFalse()
        ->and($coach->can('view', $theirPlayer))->toBeTrue()
        ->and($coach->can('view', $otherPlayer))->toBeFalse();
});
