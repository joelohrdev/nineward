<?php

use App\Models\Division;
use App\Models\Form;
use App\Models\Guardian;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;

test('records from another organization are not found', function (string $method, string $route, Closure $makeRecord, array $payload = []) {
    $organization = Organization::factory()->create();
    $theirs = $makeRecord(Organization::factory()->create());

    $this->actingAs($organization->owner)
        ->{$method}(route($route, $theirs), $payload)
        ->assertNotFound();
})->with([
    'update season' => ['put', 'organization.seasons.update', fn (Organization $other) => Season::factory()->for($other)->create(), ['name' => 'Taken', 'starts_on' => '2026-03-01', 'ends_on' => '2026-06-01', 'age_cutoff_on' => '2026-05-01']],
    'delete season' => ['delete', 'organization.seasons.destroy', fn (Organization $other) => Season::factory()->for($other)->create()],
    'update division' => ['put', 'organization.divisions.update', fn (Organization $other) => Division::factory()->for($other)->create(), ['name' => 'Taken']],
    'delete division' => ['delete', 'organization.divisions.destroy', fn (Organization $other) => Division::factory()->for($other)->create()],
    'update location' => ['put', 'organization.locations.update', fn (Organization $other) => Location::factory()->for($other)->create(), ['name' => 'Taken', 'address' => '1 Main St']],
    'delete location' => ['delete', 'organization.locations.destroy', fn (Organization $other) => Location::factory()->for($other)->create()],
    'change member role' => ['put', 'organization.members.update', fn (Organization $other) => User::factory()->memberOf($other)->create(), ['role' => 'admin']],
    'remove member' => ['delete', 'organization.members.destroy', fn (Organization $other) => User::factory()->memberOf($other)->create()],
    'view player' => ['get', 'players.show', fn (Organization $other) => Player::factory()->for($other)->create()],
    'edit player' => ['get', 'players.edit', fn (Organization $other) => Player::factory()->for($other)->create()],
    'update player' => ['put', 'players.update', fn (Organization $other) => Player::factory()->for($other)->create(), ['first_name' => 'A', 'last_name' => 'B', 'date_of_birth' => '2016-01-01']],
    'remove player' => ['delete', 'players.destroy', fn (Organization $other) => Player::factory()->for($other)->create()],
    'restore player' => ['post', 'players.restoration.store', function (Organization $other) {
        $player = Player::factory()->for($other)->create();
        $player->delete();

        return $player;
    }],
    'add guardian to player' => ['post', 'players.guardians.store', fn (Organization $other) => Player::factory()->for($other)->create(), ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com']],
    'update guardian' => ['put', 'guardians.update', fn (Organization $other) => Guardian::factory()->for($other)->create(), ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com']],
    'send claim invite' => ['post', 'guardians.claim-invitation.store', fn (Organization $other) => Guardian::factory()->for($other)->create()],
    'list teams' => ['get', 'seasons.teams.index', fn (Organization $other) => Season::factory()->for($other)->create()],
    'add players to team' => ['post', 'teams.roster-entries.store', fn (Organization $other) => Team::factory()->for(Season::factory()->for($other))->create(), ['player_ids' => [1]]],
    'open form builder' => ['get', 'forms.edit', fn (Organization $other) => Form::factory()->for($other)->create()],
    'save form draft' => ['put', 'forms.draft.update', fn (Organization $other) => Form::factory()->for($other)->create(), ['fields' => []]],
    'publish form' => ['post', 'forms.publication.store', fn (Organization $other) => Form::factory()->for($other)->create()],
    'view registration' => ['get', 'submissions.show', fn (Organization $other) => Submission::factory()->create(['form_id' => publishedRegistrationForm($other)->id])],
    'review registration' => ['post', 'submissions.review.store', fn (Organization $other) => Submission::factory()->needsReview()->create(['form_id' => publishedRegistrationForm($other)->id]), ['resolution' => 'different_player']],
    'export roster' => ['get', 'teams.roster-export.show', fn (Organization $other) => Team::factory()->for(Season::factory()->for($other))->create()],
]);
