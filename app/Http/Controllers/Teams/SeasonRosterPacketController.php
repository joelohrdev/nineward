<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Jobs\BuildSeasonRosterPacket;
use App\Models\Season;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeasonRosterPacketController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    /**
     * Start building the season's roster PDF. It's emailed when ready.
     */
    public function store(Request $request, Season $season): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        BuildSeasonRosterPacket::dispatch($season->organization_id, $season->id, $request->user()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => "We're putting the {$season->name} rosters together and will email you a link."]);

        return back();
    }

    /**
     * Download a built packet. The link is signed and expires, and still
     * requires someone who manages the organization.
     */
    public function show(Season $season): StreamedResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $path = BuildSeasonRosterPacket::path($season);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, Str::slug("{$season->name} rosters").'.pdf');
    }
}
