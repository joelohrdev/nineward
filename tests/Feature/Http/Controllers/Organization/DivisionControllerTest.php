<?php

use App\Models\Division;
use App\Models\Organization;
use App\Models\Season;
use App\Models\Team;
use Inertia\Testing\AssertableInertia as Assert;

test('lists divisions in their saved order', function () {
    $organization = Organization::factory()->create();
    Division::factory()->for($organization)->create(['name' => '11U', 'display_order' => 2]);
    Division::factory()->for($organization)->create(['name' => '10U', 'display_order' => 1]);

    $this->actingAs($organization->owner)
        ->get(route('organization.divisions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('divisions.0.name', '10U')
            ->where('divisions.1.name', '11U'));
});

test('adds a division at the end of the list', function () {
    $organization = Organization::factory()->create();
    Division::factory()->for($organization)->create(['display_order' => 4]);

    $this->actingAs($organization->owner)
        ->post(route('organization.divisions.store'), ['name' => '12U', 'max_age' => 12])
        ->assertRedirect(route('organization.divisions.index'));

    $division = Division::withoutGlobalScopes()->where('name', '12U')->sole();
    expect($division->display_order)->toBe(5)
        ->and($division->max_age)->toBe(12)
        ->and($division->min_age)->toBeNull();
});

test('rejects an oldest age below the youngest age', function () {
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.divisions.store'), ['name' => 'Odd', 'min_age' => 12, 'max_age' => 10])
        ->assertSessionHasErrors(['max_age' => 'The oldest age has to be at least the youngest age.']);
});

test('rejects a duplicate division name', function () {
    $organization = Organization::factory()->create();
    Division::factory()->for($organization)->create(['name' => '10U']);

    $this->actingAs($organization->owner)
        ->post(route('organization.divisions.store'), ['name' => '10U'])
        ->assertSessionHasErrors(['name' => 'You already have a division with that name.']);
});

test('updates and deletes a division', function () {
    $organization = Organization::factory()->create();
    $division = Division::factory()->for($organization)->create(['name' => '10U']);

    $this->actingAs($organization->owner)
        ->put(route('organization.divisions.update', $division), ['name' => '10U AAA', 'max_age' => 10])
        ->assertSessionHasNoErrors();
    expect(Division::withoutGlobalScopes()->find($division->id)->name)->toBe('10U AAA');

    $this->actingAs($organization->owner)->delete(route('organization.divisions.destroy', $division));
    expect(Division::withoutGlobalScopes()->count())->toBe(0);
});

test('a division teams use cannot be deleted', function () {
    $organization = Organization::factory()->create();
    $division = Division::factory()->for($organization)->create();
    Team::factory()->for(Season::factory()->for($organization))->create(['division_id' => $division->id]);

    $this->actingAs($organization->owner)
        ->delete(route('organization.divisions.destroy', $division))
        ->assertSessionHasErrors(['division' => "Teams use this division, so it can't be deleted. Move those teams to another division first."]);

    expect(Division::withoutGlobalScopes()->count())->toBe(1);
});
