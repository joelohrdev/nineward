<?php

namespace App\Http\Controllers\Players;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RestoredPlayerController extends Controller
{
    /**
     * Bring a removed player back to the master list.
     */
    public function store(Player $player): RedirectResponse
    {
        Gate::authorize('restore', $player);

        $player->restore();

        return to_route('players.show', $player);
    }
}
