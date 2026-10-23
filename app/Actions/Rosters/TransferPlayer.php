<?php

namespace App\Actions\Rosters;

use App\Models\RosterEntry;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferPlayer
{
    /**
     * Move a player to another team in the same season in one step. Their
     * jersey number and position stay behind, since those belong to the old team.
     */
    public function handle(RosterEntry $entry, Team $destination): RosterEntry
    {
        if ($destination->season_id !== $entry->season_id) {
            throw ValidationException::withMessages(['team_id' => 'Players can only move to a team in the same season.']);
        }

        if ($destination->id === $entry->team_id) {
            throw ValidationException::withMessages(['team_id' => 'The player is already on that team.']);
        }

        return DB::transaction(function () use ($entry, $destination): RosterEntry {
            $playerId = $entry->player_id;

            RosterEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail()->delete();

            $destination->players()->attach($playerId, ['season_id' => $destination->season_id]);

            return RosterEntry::query()
                ->where('team_id', $destination->id)
                ->where('player_id', $playerId)
                ->firstOrFail();
        });
    }
}
