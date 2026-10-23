<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\SaveSeasonRequest;
use App\Models\Season;
use App\Models\Team;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SeasonController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function index(): Response
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('manageSettings', $organization);

        $seasons = Season::query()->orderByDesc('starts_on')->orderByDesc('id')->get();
        $latest = $seasons->first();

        return Inertia::render('organization/seasons', [
            'seasonRows' => $seasons->map(fn (Season $season): array => [
                'id' => $season->id,
                'name' => $season->name,
                'startsOn' => $season->starts_on->toDateString(),
                'endsOn' => $season->ends_on->toDateString(),
                'ageCutoffOn' => $season->age_cutoff_on->toDateString(),
                'isRegistrationOpen' => $season->is_registration_open,
                'isActive' => $season->id === $organization?->active_season_id,
                'hasEnded' => $season->hasEnded(),
            ])->all(),
            'teamCounts' => Team::query()->selectRaw('season_id, count(*) as aggregate')->groupBy('season_id')->pluck('aggregate', 'season_id'),
            'suggestedAgeCutoffOn' => $latest?->age_cutoff_on->addYear()->toDateString(),
        ]);
    }

    public function store(SaveSeasonRequest $request): RedirectResponse
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('manageSettings', $organization);

        $season = Season::create($request->validated());

        if ($organization?->active_season_id === null) {
            $organization?->forceFill(['active_season_id' => $season->id])->save();
        }

        return to_route('organization.seasons.index');
    }

    public function update(SaveSeasonRequest $request, Season $season): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $season->update($request->validated());

        return to_route('organization.seasons.index');
    }

    public function destroy(Season $season): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $season->delete();

        return to_route('organization.seasons.index');
    }
}
