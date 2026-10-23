<?php

namespace App\Http\Controllers\Players;

use App\Actions\Guardians\FindOrCreateGuardian;
use App\Http\Controllers\Controller;
use App\Http\Requests\Players\SaveGuardianRequest;
use App\Models\Guardian;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PlayerGuardianController extends Controller
{
    /**
     * Add a guardian to a player. An existing guardian with the same email is
     * reused, so siblings share one guardian record.
     */
    public function store(SaveGuardianRequest $request, Player $player, FindOrCreateGuardian $findOrCreateGuardian): RedirectResponse
    {
        Gate::authorize('update', $player);

        $guardian = $findOrCreateGuardian->handle($request->guardianDetails());

        $player->guardians()->syncWithoutDetaching([
            $guardian->id => ['relationship' => $request->input('relationship')],
        ]);

        return to_route('players.show', $player);
    }

    public function destroy(Player $player, Guardian $guardian): RedirectResponse
    {
        Gate::authorize('update', $player);

        $player->guardians()->detach($guardian);

        return to_route('players.show', $player);
    }
}
