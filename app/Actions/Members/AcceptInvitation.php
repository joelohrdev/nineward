<?php

namespace App\Actions\Members;

use App\Models\Membership;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInvitation
{
    /**
     * Add the user to the inviting organization with the invited role and
     * switch them to it.
     */
    public function handle(OrganizationInvitation $invitation, User $user): void
    {
        DB::transaction(function () use ($invitation, $user): void {
            $invitation = OrganizationInvitation::query()->lockForUpdate()->findOrFail($invitation->id);

            if (! $invitation->isPending()) {
                throw ValidationException::withMessages(['invitation' => 'This invitation has expired or was already used.']);
            }

            if (! $invitation->isFor($user)) {
                throw ValidationException::withMessages(['invitation' => "This invitation was sent to {$invitation->email}."]);
            }

            $isMember = Membership::query()
                ->where('organization_id', $invitation->organization_id)
                ->where('user_id', $user->id)
                ->exists();

            if (! $isMember) {
                $invitation->organization->addMember($user, $invitation->role);
            }

            $invitation->forceFill(['accepted_at' => now()])->save();
            $user->switchOrganization($invitation->organization);
        });
    }
}
