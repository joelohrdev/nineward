<?php

namespace App\Support;

use App\Models\Guardian;
use App\Models\Player;
use App\Models\Team;

/**
 * The data a printed roster shows, shared by the single-team PDF and the
 * whole-season packet.
 */
class RosterSheet
{
    /**
     * @return array{team: string, division: string, season: string, coach: string|null, players: list<array{jersey: string, name: string, born: string, age: int, position: string, batsThrows: string, captain: bool, guardians: string}>}
     */
    public static function for(Team $team): array
    {
        $team->loadMissing(['season', 'division', 'headCoach']);

        $players = $team->players()
            ->with('guardians:id,first_name,last_name,phone,email')
            ->orderByRaw('team_player.jersey_number is null')
            ->orderBy('team_player.jersey_number')
            ->orderBy('players.last_name')
            ->orderBy('players.id')
            ->get();

        return [
            'team' => $team->name,
            'division' => $team->division->name,
            'season' => $team->season->name,
            'coach' => $team->headCoach?->name,
            'players' => array_values($players->map(fn (Player $player): array => [
                'jersey' => (string) ($player->rosterEntry->jersey_number ?? ''),
                'name' => $player->fullName(),
                'born' => $player->date_of_birth->format('M j, Y'),
                'age' => $player->leagueAgeFor($team->season),
                'position' => $player->rosterEntry->position->value ?? $player->primary_position->value ?? '',
                'batsThrows' => ($player->bats->value ?? '–').'/'.($player->throws->value ?? '–'),
                'captain' => $player->rosterEntry->is_captain ?? false,
                'guardians' => $player->guardians
                    ->map(fn (Guardian $guardian): string => trim($guardian->fullName().' '.($guardian->phone ?? $guardian->email)))
                    ->implode('; '),
            ])->all()),
        ];
    }
}
