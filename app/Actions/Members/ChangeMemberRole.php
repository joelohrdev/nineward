<?php

namespace App\Actions\Members;

use App\Enums\OrganizationRole;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeMemberRole
{
    /**
     * Change a member's role. The owner's role is fixed, and nobody else can
     * be made owner here.
     */
    public function handle(Organization $organization, User $member, OrganizationRole $role): void
    {
        if ($role === OrganizationRole::Owner) {
            throw ValidationException::withMessages(['role' => 'An organization has exactly one owner.']);
        }

        DB::transaction(function () use ($organization, $member, $role): void {
            $membership = Membership::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $member->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($membership->role === OrganizationRole::Owner) {
                throw ValidationException::withMessages(['role' => "The owner's role can't be changed."]);
            }

            $membership->role = $role;
            $membership->save();
        });
    }
}
