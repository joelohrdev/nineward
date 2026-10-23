<?php

use App\Models\Location;
use App\Models\Organization;

test('adds, updates and deletes a location', function () {
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.locations.store'), [
            'name' => 'Main Park Field 1',
            'address' => '100 Park Ave, Cary',
            'maps_url' => 'https://maps.example.com/place/1',
        ])
        ->assertRedirect(route('organization.locations.index'));

    $location = Location::withoutGlobalScopes()->sole();
    expect($location->organization_id)->toBe($organization->id);

    $this->actingAs($organization->owner)
        ->put(route('organization.locations.update', $location), ['name' => 'Main Park Field 2', 'address' => '100 Park Ave, Cary', 'maps_url' => ''])
        ->assertSessionHasNoErrors();
    $location = Location::withoutGlobalScopes()->find($location->id);
    expect($location->name)->toBe('Main Park Field 2')
        ->and($location->maps_url)->toBeNull();

    $this->actingAs($organization->owner)->delete(route('organization.locations.destroy', $location));
    expect(Location::withoutGlobalScopes()->count())->toBe(0);
});

test('map links must be https URLs', function () {
    $organization = Organization::factory()->create();

    $this->actingAs($organization->owner)
        ->post(route('organization.locations.store'), [
            'name' => 'Field',
            'address' => '1 Main St',
            'maps_url' => 'javascript:alert(1)',
        ])
        ->assertSessionHasErrors(['maps_url' => 'The map link field must be a valid URL.']);
});
