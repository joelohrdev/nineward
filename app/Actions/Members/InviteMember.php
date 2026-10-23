<?php

namespace App\Actions\Members;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\OrganizationInvitationSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class InviteMember
{
    /**
     * Invite someone by email, or refresh and resend an existing invitation.
     */
    public function handle(Organization $organization, User $inviter, string $email, OrganizationRole $role): OrganizationInvitation
    {
        $invitation = DB::transaction(function () use ($organization, $inviter, $email, $role): OrganizationInvitation {
            $invitation = $organization->invitations()->firstOrNew(['email' => mb_strtolower(trim($email))]);
            $invitation->role = $role;
            $invitation->expires_at = now()->addDays(OrganizationInvitation::VALID_FOR_DAYS);
            $invitation->accepted_at = null;
            $invitation->inviter()->associate($inviter);
            $invitation->save();

            return $invitation;
        });

        Notification::route('mail', $invitation->email)->notify(new OrganizationInvitationSent($invitation));

        return $invitation;
    }
}
