<?php

namespace App\Http\Controllers;

use App\Actions\Members\AcceptInvitation;
use App\Http\Middleware\EnsureUserHasOrganization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * The page an invitation link opens. Guests are asked to log in or sign
     * up with the invited email address, then come back here.
     */
    public function show(Request $request, OrganizationInvitation $invitation): Response
    {
        $invitation->load('organization', 'inviter:id,name');
        $user = $request->user();

        if (! $user instanceof User) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        if ($invitation->isPending()) {
            $request->session()->put(EnsureUserHasOrganization::PENDING_LINK_SESSION_KEY, $request->fullUrl());
        }

        return Inertia::render('invitations/show', [
            'invitation' => [
                'organizationName' => $invitation->organization->name,
                'organizationColor' => $invitation->organization->primary_color,
                'inviterName' => $invitation->inviter->name,
                'email' => $invitation->email,
                'role' => $invitation->role->label(),
                'isPending' => $invitation->isPending(),
            ],
            'signedInEmail' => $user instanceof User ? $user->email : null,
            'isForSignedInUser' => $user instanceof User && $invitation->isFor($user),
            'acceptUrl' => URL::temporarySignedRoute('invitations.acceptance.store', $invitation->expires_at, ['invitation' => $invitation->id]),
        ]);
    }

    public function accept(Request $request, OrganizationInvitation $invitation, AcceptInvitation $acceptInvitation): RedirectResponse
    {
        $acceptInvitation->handle($invitation, $request->user());

        $request->session()->forget(EnsureUserHasOrganization::PENDING_LINK_SESSION_KEY);

        return to_route('dashboard');
    }
}
