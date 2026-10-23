<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Members\ChangeMemberRole;
use App\Actions\Members\RemoveMember;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateMemberRoleRequest;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function index(Request $request): Response
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('manageMembers', $organization);

        $members = $organization->members()->orderBy('users.name')->orderBy('users.id')->get();

        $invitations = $organization->invitations()
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('organization/members', [
            'members' => $members->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->membership?->role->value,
                'isOwner' => $member->membership?->role === OrganizationRole::Owner,
                'isYou' => $member->id === $request->user()?->id,
                'joinedAt' => $member->membership?->created_at?->toIso8601String(),
            ])->all(),
            'invitations' => $invitations->map(fn (OrganizationInvitation $invitation): array => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'expiresAt' => $invitation->expires_at->toIso8601String(),
                'hasExpired' => ! $invitation->isPending(),
            ])->all(),
        ]);
    }

    public function update(UpdateMemberRoleRequest $request, User $member, ChangeMemberRole $changeMemberRole): RedirectResponse
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('manageMembers', $organization);

        $changeMemberRole->handle($organization, $member, $request->enum('role', OrganizationRole::class));

        return to_route('organization.members.index');
    }

    public function destroy(User $member, RemoveMember $removeMember): RedirectResponse
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('manageMembers', $organization);

        $removeMember->handle($organization, $member);

        return to_route('organization.members.index');
    }
}
