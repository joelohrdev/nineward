<?php

namespace App\Actions\Rosters;

use App\Models\Team;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddPlayersToTeam
{
    /**
     * Put players on the team's roster for its season. The database allows a
     * player on only one team per season, so if another request rostered one
     * of them first, nothing is added and the user is told to refresh.
     *
     * @param  list<int>  $playerIds
     */
    public function handle(Team $team, array $playerIds): void
    {
        try {
            DB::transaction(function () use ($team, $playerIds): void {
                $team->players()->attach(
                    collect($playerIds)->mapWithKeys(fn (int $playerId): array => [$playerId => ['season_id' => $team->season_id]])->all(),
                );
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'player_ids' => 'Someone just put one of these players on a team. Refresh and try again.',
            ]);
        }
    }
}
