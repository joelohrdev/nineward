<?php

use App\Actions\Guardians\FindOrCreateGuardian;
use App\Models\Guardian;
use App\Models\Organization;
use App\Support\CurrentOrganization;

test('reuses a guardian with the same email in any letter case', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $existing = Guardian::factory()->for($organization)->create(['email' => 'pat@example.com', 'first_name' => 'Pat', 'phone' => null]);

    $guardian = app(FindOrCreateGuardian::class)->handle([
        'first_name' => 'Patricia',
        'last_name' => 'Lee',
        'email' => ' PAT@Example.com ',
        'phone' => '919-555-0100',
    ]);

    expect($guardian->id)->toBe($existing->id)
        ->and($guardian->first_name)->toBe('Pat')
        ->and($guardian->phone)->toBe('919-555-0100')
        ->and(Guardian::count())->toBe(1);
});

test('the same email in another organization is a different guardian', function () {
    $organization = Organization::factory()->create();
    Guardian::factory()->create(['email' => 'pat@example.com']);
    app(CurrentOrganization::class)->set($organization);

    $guardian = app(FindOrCreateGuardian::class)->handle([
        'first_name' => 'Pat',
        'last_name' => 'Lee',
        'email' => 'pat@example.com',
    ]);

    expect($guardian->organization_id)->toBe($organization->id)
        ->and(Guardian::withoutGlobalScopes()->count())->toBe(2);
});
