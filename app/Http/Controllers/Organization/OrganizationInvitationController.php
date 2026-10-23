<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Members\InviteMember;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreInvitationRequest;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class OrganizationInvitationController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    /**
     * Invite someone, or resend an invitation that's already out.
     */
    public function store(StoreInvitationRequest $request, InviteMember $inviteMember): RedirectResponse
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('manageMembers', $organization);

        $inviteMember->handle(
            $organization,
            $request->user(),
            $request->string('email')->toString(),
            $request->enum('role', OrganizationRole::class),
        );

        return to_route('organization.members.index');
    }

    public function destroy(int $invitation): RedirectResponse
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('manageMembers', $organization);

        $organization->invitations()->findOrFail($invitation)->delete();

        return to_route('organization.members.index');
    }
}
