<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Season;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('index', function () {
    test('lists the organization\'s seasons newest first and suggests next year\'s age cutoff', function () {
        $organization = Organization::factory()->create();
        Season::factory()->for($organization)->create(['name' => 'Spring 2025', 'starts_on' => '2025-03-01', 'age_cutoff_on' => '2025-05-01']);
        Season::factory()->for($organization)->create(['name' => 'Fall 2025', 'starts_on' => '2025-09-01', 'age_cutoff_on' => '2025-08-31']);
        Season::factory()->create(['name' => 'Elsewhere 2025']);

        $this->actingAs($organization->owner)
            ->get(route('organization.seasons.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('organization/seasons')
                ->has('seasonRows', 2)
                ->where('seasonRows.0.name', 'Fall 2025')
                ->where('suggestedAgeCutoffOn', '2026-08-31')
                ->has('seasons.0.isActive'));
    });

    test('coaches cannot manage seasons', function () {
        $coach = User::factory()->memberOf(Organization::factory()->create(), OrganizationRole::Coach)->create();

        $this->actingAs($coach)
            ->get(route('organization.seasons.index'))
            ->assertForbidden();
    });
});

describe('store', function () {
    test('creates a season and makes the first one active', function () {
        $organization = Organization::factory()->create();

        $this->actingAs($organization->owner)
            ->post(route('organization.seasons.store'), [
                'name' => 'Spring 2026',
                'starts_on' => '2026-03-01',
                'ends_on' => '2026-06-30',
                'age_cutoff_on' => '2026-05-01',
                'is_registration_open' => '1',
            ])
            ->assertRedirect(route('organization.seasons.index'));

        $season = Season::withoutGlobalScopes()->sole();
        expect($season->organization_id)->toBe($organization->id)
            ->and($season->is_registration_open)->toBeTrue()
            ->and($organization->refresh()->active_season_id)->toBe($season->id);
    });

    test('a second season does not replace the active one', function () {
        $organization = Organization::factory()->create();
        $active = Season::factory()->for($organization)->create();
        $organization->forceFill(['active_season_id' => $active->id])->save();

        $this->actingAs($organization->owner)->post(route('organization.seasons.store'), [
            'name' => 'Fall 2026',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-11-30',
            'age_cutoff_on' => '2026-08-31',
        ]);

        expect($organization->refresh()->active_season_id)->toBe($active->id);
    });

    test('requires every field', function () {
        $organization = Organization::factory()->create();

        $this->actingAs($organization->owner)
            ->post(route('organization.seasons.store'), [])
            ->assertSessionHasErrors([
                'name' => 'The name field is required.',
                'starts_on' => 'The start date field is required.',
                'ends_on' => 'The end date field is required.',
                'age_cutoff_on' => 'The age cutoff date field is required.',
            ]);
    });

    test('rejects a season that ends before it starts', function () {
        $organization = Organization::factory()->create();

        $this->actingAs($organization->owner)
            ->post(route('organization.seasons.store'), [
                'name' => 'Backwards',
                'starts_on' => '2026-06-01',
                'ends_on' => '2026-03-01',
                'age_cutoff_on' => '2026-05-01',
            ])
            ->assertSessionHasErrors(['ends_on' => 'The season has to end on or after its start date.']);
    });

    test('rejects a duplicate name within the organization but allows it in another', function () {
        $organization = Organization::factory()->create();
        Season::factory()->for($organization)->create(['name' => 'Spring 2026']);
        Season::factory()->create(['name' => 'Fall 2026']);
        $payload = ['starts_on' => '2026-03-01', 'ends_on' => '2026-06-30', 'age_cutoff_on' => '2026-05-01'];

        $this->actingAs($organization->owner)
            ->post(route('organization.seasons.store'), ['name' => 'Spring 2026', ...$payload])
            ->assertSessionHasErrors(['name' => 'You already have a season with that name.']);

        $this->actingAs($organization->owner)
            ->post(route('organization.seasons.store'), ['name' => 'Fall 2026', ...$payload])
            ->assertSessionHasNoErrors();
    });
});

describe('update', function () {
    test('updates a season and closes registration when the box is unchecked', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create(['name' => 'Spring 2026', 'is_registration_open' => true]);

        $this->actingAs($organization->owner)
            ->put(route('organization.seasons.update', $season), [
                'name' => 'Spring 2026',
                'starts_on' => '2026-03-02',
                'ends_on' => '2026-06-30',
                'age_cutoff_on' => '2026-05-01',
            ])
            ->assertSessionHasNoErrors();

        $season = Season::withoutGlobalScopes()->find($season->id);
        expect($season->starts_on->toDateString())->toBe('2026-03-02')
            ->and($season->is_registration_open)->toBeFalse();
    });
});

describe('destroy', function () {
    test('deletes a season', function () {
        $organization = Organization::factory()->create();
        $season = Season::factory()->for($organization)->create();

        $this->actingAs($organization->owner)
            ->delete(route('organization.seasons.destroy', $season))
            ->assertRedirect(route('organization.seasons.index'));

        expect(Season::withoutGlobalScopes()->count())->toBe(0);
    });
});
