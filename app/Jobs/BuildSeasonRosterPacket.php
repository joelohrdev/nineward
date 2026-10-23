<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Notifications\SeasonRosterPacketReady;
use App\Support\CurrentOrganization;
use App\Support\RosterSheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Every roster in a season as one PDF. Big organizations can have dozens of
 * teams, so this runs on the queue and emails a private download link.
 */
class BuildSeasonRosterPacket implements ShouldQueue
{
    use Queueable;

    /** Below the queue's retry_after (90s), so a slow build is never picked up twice. */
    public int $timeout = 60;

    public int $tries = 2;

    /**
     * IDs rather than models: the season is tenant-scoped, so the job sets
     * the organization itself before loading anything.
     */
    public function __construct(public int $organizationId, public int $seasonId, public int $requestedById) {}

    public function handle(CurrentOrganization $currentOrganization): void
    {
        $currentOrganization->set(Organization::findOrFail($this->organizationId));

        $season = Season::with(['organization', 'teams.division'])->findOrFail($this->seasonId);
        $requestedBy = User::findOrFail($this->requestedById);

        $teams = $season->teams
            ->sortBy([
                fn (Team $a, Team $b) => $a->division->display_order <=> $b->division->display_order,
                fn (Team $a, Team $b) => strcmp($a->name, $b->name),
            ]);

        $pdf = Pdf::loadView('pdf.rosters', [
            'title' => "{$season->name} rosters",
            'organization' => $season->organization->name,
            'sheets' => $teams->map(fn (Team $team): array => RosterSheet::for($team))->values()->all(),
            'includeContacts' => true,
            'printedAt' => now()->format('M j, Y'),
        ]);

        // Deterministic per season, so a retry overwrites instead of piling up files.
        $path = self::path($season);
        Storage::disk('local')->put($path, $pdf->output());

        $requestedBy->notify(new SeasonRosterPacketReady($season));
    }

    /**
     * Where a season's packet is stored, on the private disk.
     */
    public static function path(Season $season): string
    {
        return 'roster-packets/'.$season->organization_id.'/'.Str::slug($season->name).'-'.$season->id.'.pdf';
    }
}
