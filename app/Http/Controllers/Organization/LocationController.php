<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\SaveLocationRequest;
use App\Models\Location;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function index(): Response
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        return Inertia::render('organization/locations', [
            'locations' => Location::query()
                ->orderBy('name')
                ->orderBy('id')
                ->get()
                ->map(fn (Location $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'address' => $location->address,
                    'mapsUrl' => $location->maps_url,
                ])
                ->all(),
        ]);
    }

    public function store(SaveLocationRequest $request): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        Location::create($request->validated());

        return to_route('organization.locations.index');
    }

    public function update(SaveLocationRequest $request, Location $location): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $location->update($request->validated());

        return to_route('organization.locations.index');
    }

    public function destroy(Location $location): RedirectResponse
    {
        Gate::authorize('manageSettings', $this->currentOrganization->get());

        $location->delete();

        return to_route('organization.locations.index');
    }
}
