<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationRoleResolver;

class OrganizationPolicy
{
    public function __construct(private OrganizationRoleResolver $roles) {}

    /**
     * Any member can see the organization's billing status.
     */
    public function viewBilling(User $user, Organization $organization): bool
    {
        return $this->roles->for($user, $organization) !== null;
    }

    /**
     * Only the owner can subscribe or change the organization's billing.
     */
    public function manageBilling(User $user, Organization $organization): bool
    {
        return $this->roles->for($user, $organization) === OrganizationRole::Owner;
    }

    /**
     * Owners and admins manage seasons, divisions, locations and the organization's profile.
     */
    public function manageSettings(User $user, Organization $organization): bool
    {
        return $this->roles->for($user, $organization)?->canManageOrganization() ?? false;
    }

    /**
     * Owners and admins invite people and change members' roles.
     */
    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->roles->for($user, $organization)?->canManageOrganization() ?? false;
    }

    /**
     * Only the owner connects or disconnects the Stripe account fees are paid into.
     */
    public function managePayments(User $user, Organization $organization): bool
    {
        return $this->roles->for($user, $organization) === OrganizationRole::Owner;
    }
}
