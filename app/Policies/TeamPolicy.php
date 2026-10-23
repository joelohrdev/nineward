<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Support\CurrentOrganization;
use App\Support\OrganizationRoleResolver;

/**
 * Owners and admins run every team. A coach sees and edits the roster of the
 * teams they head while the season is running, but can't create, rename or
 * delete teams.
 */
class TeamPolicy
{
    public function __construct(
        private OrganizationRoleResolver $roles,
        private CurrentOrganization $currentOrganization,
    ) {}

    /**
     * Every member can open the teams list. Coaches only see their own teams in it.
     */
    public function viewAny(User $user): bool
    {
        $organization = $this->currentOrganization->get();

        return $organization !== null && $this->roles->for($user, $organization) !== null;
    }

    public function view(User $user, Team $team): bool
    {
        return $this->manages($user, $team->organization) || $team->isHeadedBy($user);
    }

    public function create(User $user): bool
    {
        return $this->manages($user, $this->currentOrganization->get());
    }

    public function update(User $user, Team $team): bool
    {
        return $this->manages($user, $team->organization);
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->manages($user, $team->organization);
    }

    /**
     * Add, edit, move and remove players. Once a season has ended its rosters
     * are history: only owners and admins can still change them.
     */
    public function manageRoster(User $user, Team $team): bool
    {
        return $this->manages($user, $team->organization)
            || ($team->isHeadedBy($user) && ! $team->season->hasEnded());
    }

    /**
     * Download the roster. Head coaches can export past seasons too.
     */
    public function exportRoster(User $user, Team $team): bool
    {
        return $this->manages($user, $team->organization) || $team->isHeadedBy($user);
    }

    private function manages(User $user, ?Organization $organization): bool
    {
        return $organization !== null
            && ($this->roles->for($user, $organization)?->canManageOrganization() ?? false);
    }
}
