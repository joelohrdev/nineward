<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Rosters\TransferPlayer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\StoreRosterTransferRequest;
use App\Models\RosterEntry;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RosterTransferController extends Controller
{
    /**
     * Move a player straight from this team to another one in the season.
     */
    public function store(StoreRosterTransferRequest $request, Team $team, RosterEntry $rosterEntry, TransferPlayer $transferPlayer): RedirectResponse
    {
        $destination = Team::findOrFail($request->integer('team_id'));

        Gate::authorize('manageRoster', $team);
        Gate::authorize('manageRoster', $destination);

        $transferPlayer->handle($rosterEntry, $destination);

        return back();
    }
}
