<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureUserHasOrganization;
use App\Models\User;
use App\Support\GuardianAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class GuardianClaimController extends Controller
{
    public function __construct(private GuardianAccounts $guardianAccounts) {}

    /**
     * The page a claim link opens. Guests are asked to log in or sign up with
     * the guardian's email address, then come back here.
     */
    public function show(Request $request, int $guardian): Response
    {
        $record = $this->guardianAccounts->findForClaim($guardian);
        $user = $request->user();

        if (! $user instanceof User) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        if (! $record->isClaimed()) {
            $request->session()->put(EnsureUserHasOrganization::PENDING_LINK_SESSION_KEY, $request->fullUrl());
        }

        return Inertia::render('guardian-claims/show', [
            'claim' => [
                'organizationName' => $record->organization->name,
                'organizationColor' => $record->organization->primary_color,
                'guardianName' => $record->first_name,
                'email' => $record->email,
                'isClaimed' => $record->isClaimed(),
                'isClaimedBySignedInUser' => $user instanceof User && $record->user_id === $user->id,
            ],
            'signedInEmail' => $user instanceof User ? $user->email : null,
            'isForSignedInUser' => $user instanceof User && mb_strtolower($user->email) === $record->email,
            'claimUrl' => URL::temporarySignedRoute('guardian-claims.store', now()->addHour(), ['guardian' => $record->id]),
        ]);
    }

    public function store(Request $request, int $guardian): RedirectResponse
    {
        $this->guardianAccounts->claim($this->guardianAccounts->findForClaim($guardian), $request->user());

        $request->session()->forget(EnsureUserHasOrganization::PENDING_LINK_SESSION_KEY);

        return to_route('my-players.index');
    }
}
