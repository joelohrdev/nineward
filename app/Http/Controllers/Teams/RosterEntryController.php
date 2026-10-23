<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Rosters\AddPlayersToTeam;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\StoreRosterEntriesRequest;
use App\Http\Requests\Teams\UpdateRosterEntryRequest;
use App\Models\RosterEntry;
use App\Models\Team;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RosterEntryController extends Controller
{
    /**
     * Put the selected players on the team.
     */
    public function store(StoreRosterEntriesRequest $request, Team $team, AddPlayersToTeam $addPlayersToTeam): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $addPlayersToTeam->handle($team, $request->playerIds());

        return back();
    }

    /**
     * Change a player's jersey number, position or captaincy.
     */
    public function update(UpdateRosterEntryRequest $request, Team $team, RosterEntry $rosterEntry): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        try {
            $rosterEntry->update($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['jersey_number' => 'Someone on this team already wears that number.']);
        }

        return back();
    }

    /**
     * Take a player off the team. They go back to the season's unassigned pool.
     */
    public function destroy(Team $team, RosterEntry $rosterEntry): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $rosterEntry->delete();

        return back();
    }
}
