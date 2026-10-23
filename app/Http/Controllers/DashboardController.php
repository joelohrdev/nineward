<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Team;
use App\Support\CurrentOrganization;
use App\Support\SelectedSeason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CurrentOrganization $currentOrganization, SelectedSeason $selectedSeason): Response
    {
        $organization = $currentOrganization->get();
        $season = $selectedSeason->get();
        $canManage = $organization !== null && Gate::allows('manageSettings', $organization);

        return Inertia::render('dashboard', [
            'season' => $season === null ? null : [
                'id' => $season->id,
                'name' => $season->name,
                'isActive' => $season->id === $organization?->active_season_id,
            ],
            'activeSeasonName' => $organization?->activeSeason?->name,
            'counts' => $season === null || ! $canManage ? null : [
                'players' => Player::count(),
                'unassigned' => Player::unassignedFor($season)->count(),
                'rostered' => Player::rosteredIn($season)->count(),
                'teams' => $season->teams()->count(),
            ],
            'myTeams' => $season === null ? [] : $season->teams()
                ->where('head_coach_id', $request->user()?->id)
                ->withCount('players')
                ->orderBy('name')
                ->orderBy('id')
                ->get()
                ->map(fn (Team $team): array => ['slug' => $team->slug, 'name' => $team->name, 'playersCount' => $team->players_count])
                ->all(),
            'canManage' => $canManage,
        ]);
    }
}
