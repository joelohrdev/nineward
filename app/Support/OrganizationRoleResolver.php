<?php

namespace App\Support;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

/**
 * Works out the role a user acts with in an organization. Platform admins in
 * a support session act as an Admin of that one organization.
 */
class OrganizationRoleResolver
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function for(User $user, Organization $organization): ?OrganizationRole
    {
        if ($user->is_platform_admin
            && $this->currentOrganization->isSupportSession()
            && $this->currentOrganization->id() === $organization->id) {
            return OrganizationRole::Admin;
        }

        return $user->roleIn($organization);
    }
}
