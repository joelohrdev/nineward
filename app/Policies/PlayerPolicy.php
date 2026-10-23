<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Player;
use App\Models\User;
use App\Support\CurrentOrganization;
use App\Support\OrganizationRoleResolver;

class PlayerPolicy
{
    public function __construct(
        private OrganizationRoleResolver $roles,
        private CurrentOrganization $currentOrganization,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->managesCurrentOrganization($user);
    }

    /**
     * Managers see every player. Coaches see players on teams they head or have headed.
     */
    public function view(User $user, Player $player): bool
    {
        return $this->manages($user, $player->organization)
            || $player->rosterEntries()
                ->whereHas('team', fn ($teams) => $teams->withTrashed()->where('head_coach_id', $user->id))
                ->exists();
    }

    public function create(User $user): bool
    {
        return $this->managesCurrentOrganization($user);
    }

    public function update(User $user, Player $player): bool
    {
        return $this->manages($user, $player->organization);
    }

    public function delete(User $user, Player $player): bool
    {
        return $this->manages($user, $player->organization);
    }

    public function restore(User $user, Player $player): bool
    {
        return $this->manages($user, $player->organization);
    }

    private function managesCurrentOrganization(User $user): bool
    {
        return $this->manages($user, $this->currentOrganization->get());
    }

    private function manages(User $user, ?Organization $organization): bool
    {
        return $organization !== null
            && ($this->roles->for($user, $organization)?->canManageOrganization() ?? false);
    }
}
