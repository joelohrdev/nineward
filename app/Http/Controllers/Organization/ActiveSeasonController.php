<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateActiveSeasonRequest;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ActiveSeasonController extends Controller
{
    /**
     * Make a season the one the organization is currently running.
     */
    public function update(UpdateActiveSeasonRequest $request, CurrentOrganization $currentOrganization): RedirectResponse
    {
        $organization = $currentOrganization->get();
        Gate::authorize('manageSettings', $organization);

        $organization?->forceFill(['active_season_id' => $request->integer('season_id')])->save();

        return to_route('organization.seasons.index');
    }
}
