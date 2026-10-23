<?php

namespace App\Policies;

use App\Models\Guardian;
use App\Models\User;
use App\Support\OrganizationRoleResolver;

/**
 * Guardian contact details are visible to the organization's owners and
 * admins, and to coaches for the players on teams they head or have headed.
 */
class GuardianPolicy
{
    public function __construct(private OrganizationRoleResolver $roles) {}

    public function view(User $user, Guardian $guardian): bool
    {
        return $this->manages($user, $guardian)
            || $guardian->players()
                ->whereHas('rosterEntries.team', fn ($teams) => $teams->withTrashed()->where('head_coach_id', $user->id))
                ->exists();
    }

    public function update(User $user, Guardian $guardian): bool
    {
        return $this->manages($user, $guardian);
    }

    /**
     * Sending a claim invite emails the guardian a link to their account.
     */
    public function invite(User $user, Guardian $guardian): bool
    {
        return $this->manages($user, $guardian) && ! $guardian->isClaimed();
    }

    private function manages(User $user, Guardian $guardian): bool
    {
        return $this->roles->for($user, $guardian->organization)?->canManageOrganization() ?? false;
    }
}
