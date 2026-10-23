<?php

use App\Actions\Organizations\CreateOrganization;
use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('creates the organization with the user as owner and starts the free trial', function () {
    config(['services.stripe.trial_days' => 14]);
    $this->freezeSecond();
    $owner = User::factory()->create();

    $organization = app(CreateOrganization::class)->handle($owner, 'Cary Trojans Baseball');

    expect($organization->slug)->toBe('cary-trojans-baseball')
        ->and($organization->owner_id)->toBe($owner->id)
        ->and($organization->trial_ends_at->equalTo(now()->addDays(14)))->toBeTrue()
        ->and($owner->roleIn($organization))->toBe(OrganizationRole::Owner)
        ->and($owner->refresh()->current_organization_id)->toBe($organization->id);
});

test('gives each organization a unique slug, including after one is deleted', function () {
    Organization::factory()->create(['slug' => 'cary-trojans']);
    Organization::factory()->create(['slug' => 'cary-trojans-2'])->delete();

    $organization = app(CreateOrganization::class)->handle(User::factory()->create(), 'Cary Trojans');

    expect($organization->slug)->toBe('cary-trojans-3');
});
