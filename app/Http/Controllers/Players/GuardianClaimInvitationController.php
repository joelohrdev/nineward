<?php

namespace App\Http\Controllers\Players;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Notifications\GuardianClaimInvitationSent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class GuardianClaimInvitationController extends Controller
{
    /**
     * Email a guardian a link to see their players with their own account.
     */
    public function store(Guardian $guardian): RedirectResponse
    {
        Gate::authorize('invite', $guardian);

        Notification::route('mail', $guardian->email)->notify(new GuardianClaimInvitationSent($guardian));

        return back();
    }
}
