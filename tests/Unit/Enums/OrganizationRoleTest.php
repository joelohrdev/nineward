<?php

use App\Enums\OrganizationRole;

test('only owners and admins can manage the organization', function (OrganizationRole $role, bool $canManage) {
    expect($role->canManageOrganization())->toBe($canManage);
})->with([
    'owner' => [OrganizationRole::Owner, true],
    'admin' => [OrganizationRole::Admin, true],
    'coach' => [OrganizationRole::Coach, false],
]);
