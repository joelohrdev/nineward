<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeamRosterExportController extends Controller
{
    /**
     * Download the roster as a CSV, for tournaments and team parents.
     */
    public function show(Team $team): StreamedResponse
    {
        Gate::authorize('exportRoster', $team);

        $team->load('season');
        $filename = Str::slug("{$team->season->name} {$team->name} roster").'.csv';

        return response()->streamDownload(function () use ($team): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fputcsv($output, ['Jersey', 'First name', 'Last name', 'Date of birth', 'League age', 'Bats', 'Throws', 'Position', 'Captain', 'Guardians', 'Guardian emails', 'Guardian phones'], escape: '');

            $team->players()
                ->with('guardians:id,first_name,last_name,email,phone')
                ->orderByRaw('team_player.jersey_number is null')
                ->orderBy('team_player.jersey_number')
                ->orderBy('players.last_name')
                ->orderBy('players.id')
                ->get()
                ->each(function (Player $player) use ($output, $team): void {
                    fputcsv($output, array_map(self::safeCell(...), [
                        (string) ($player->rosterEntry->jersey_number ?? ''),
                        $player->first_name,
                        $player->last_name,
                        $player->date_of_birth->toDateString(),
                        (string) $player->leagueAgeFor($team->season),
                        $player->bats->value ?? '',
                        $player->throws->value ?? '',
                        $player->rosterEntry?->position->value ?? $player->primary_position->value ?? '',
                        $player->rosterEntry?->is_captain ? 'Yes' : '',
                        $player->guardians->map(fn (Guardian $guardian): string => $guardian->fullName())->implode('; '),
                        $player->guardians->pluck('email')->implode('; '),
                        $player->guardians->pluck('phone')->filter()->implode('; '),
                    ]), escape: '');
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Stops spreadsheet apps from running a cell as a formula.
     */
    private static function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'{$value}" : $value;
    }
}
