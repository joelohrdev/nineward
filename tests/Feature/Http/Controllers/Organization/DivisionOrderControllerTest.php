<?php

use App\Models\Division;
use App\Models\Organization;

test('saves a new division order', function () {
    $organization = Organization::factory()->create();
    $first = Division::factory()->for($organization)->create(['display_order' => 1]);
    $second = Division::factory()->for($organization)->create(['display_order' => 2]);

    $this->actingAs($organization->owner)
        ->put(route('organization.division-order.update'), ['division_ids' => [$second->id, $first->id]])
        ->assertRedirect(route('organization.divisions.index'));

    expect(Division::withoutGlobalScopes()->find($second->id)->display_order)->toBe(1)
        ->and(Division::withoutGlobalScopes()->find($first->id)->display_order)->toBe(2);
});

test('rejects an order that leaves a division out or includes another organization\'s', function () {
    $organization = Organization::factory()->create();
    $ours = Division::factory()->for($organization)->create();
    Division::factory()->for($organization)->create();
    $theirs = Division::factory()->create();

    $this->actingAs($organization->owner)
        ->put(route('organization.division-order.update'), ['division_ids' => [$ours->id, $theirs->id]])
        ->assertSessionHasErrors(['division_ids' => 'The divisions changed while you were reordering. Refresh and try again.']);

    expect(Division::withoutGlobalScopes()->find($theirs->id)->display_order)->toBe(0);
});
