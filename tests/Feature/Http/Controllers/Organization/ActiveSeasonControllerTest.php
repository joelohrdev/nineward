<?php

use App\Models\Organization;
use App\Models\Season;

test('makes a season active', function () {
    $organization = Organization::factory()->create();
    $season = Season::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->put(route('organization.active-season.update'), ['season_id' => $season->id])
        ->assertRedirect(route('organization.seasons.index'));

    expect($organization->refresh()->active_season_id)->toBe($season->id);
});

test('cannot activate another organization\'s season', function () {
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->put(route('organization.active-season.update'), ['season_id' => Season::factory()->create()->id])
        ->assertSessionHasErrors('season_id');

    expect($organization->refresh()->active_season_id)->toBeNull();
});
