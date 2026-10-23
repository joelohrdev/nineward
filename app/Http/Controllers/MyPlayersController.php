<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Support\GuardianAccounts;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyPlayersController extends Controller
{
    /**
     * The players a parent has claimed, across every organization.
     */
    public function index(Request $request, GuardianAccounts $guardianAccounts): Response
    {
        return Inertia::render('my-players/index', [
            'players' => $guardianAccounts->playersFor($request->user())->map(fn (Player $player): array => [
                'id' => $player->id,
                'name' => $player->fullName(),
                'dateOfBirth' => $player->date_of_birth->toDateString(),
                'position' => $player->primary_position?->label(),
                'organizationName' => $player->organization->name,
                'organizationColor' => $player->organization->primary_color,
            ])->all(),
        ]);
    }
}
