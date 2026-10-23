<?php

namespace App\Actions\Members;

use App\Enums\OrganizationRole;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveMember
{
    /**
     * Remove someone from the organization. The owner can't be removed.
     */
    public function handle(Organization $organization, User $member): void
    {
        DB::transaction(function () use ($organization, $member): void {
            $membership = Membership::query()
                ->where('organization_id', $organization->id)
                ->where('user_id', $member->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($membership->role === OrganizationRole::Owner) {
                throw ValidationException::withMessages(['member' => "The owner can't be removed."]);
            }

            $membership->delete();

            if ($member->current_organization_id === $organization->id) {
                $member->forceFill(['current_organization_id' => null])->save();
            }
        });
    }
}
