<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Support\RosterSheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TeamRosterPdfController extends Controller
{
    /**
     * A printable roster for tournaments and the dugout.
     */
    public function show(Team $team): Response
    {
        Gate::authorize('exportRoster', $team);

        $sheet = RosterSheet::for($team);

        return Pdf::loadView('pdf.rosters', [
            'title' => "{$sheet['season']} {$sheet['team']} roster",
            'organization' => $team->organization->name,
            'sheets' => [$sheet],
            'includeContacts' => true,
            'printedAt' => now()->format('M j, Y'),
        ])->download(Str::slug("{$sheet['season']} {$sheet['team']} roster").'.pdf');
    }
}
