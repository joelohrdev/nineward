<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;

test('billing abilities by role', function (OrganizationRole $role, bool $canView, bool $canManage) {
    $organization = Organization::factory()->create();
    $user = User::factory()->memberOf($organization, $role)->create();

    expect($user->can('viewBilling', $organization))->toBe($canView)
        ->and($user->can('manageBilling', $organization))->toBe($canManage);
})->with([
    'owner' => [OrganizationRole::Owner, true, true],
    'admin' => [OrganizationRole::Admin, true, false],
    'coach' => [OrganizationRole::Coach, true, false],
]);

test('non-members have no billing abilities', function () {
    $organization = Organization::factory()->create();
    $outsider = User::factory()->create();

    expect($outsider->can('viewBilling', $organization))->toBeFalse()
        ->and($outsider->can('manageBilling', $organization))->toBeFalse();
});

test('platform admins in a support session can view billing but not manage it', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->platformAdmin()->create();
    app(CurrentOrganization::class)->setForSupport($organization);

    expect($admin->can('viewBilling', $organization))->toBeTrue()
        ->and($admin->can('manageBilling', $organization))->toBeFalse();
});
