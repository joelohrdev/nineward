<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateOrganizationProfileRequest;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationProfileController extends Controller
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    public function edit(): Response
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('manageSettings', $organization);

        return Inertia::render('organization/profile', [
            'profile' => [
                'name' => $organization->name,
                'slug' => $organization->slug,
                'primaryColor' => $organization->primary_color,
                'logoUrl' => $organization->logoUrl(),
                'showPublicRosters' => $organization->show_public_rosters,
                'publicUrl' => route('organizations.public.show', $organization->slug),
            ],
        ]);
    }

    public function update(UpdateOrganizationProfileRequest $request): RedirectResponse
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('manageSettings', $organization);

        $previousLogo = $organization->logo_path;

        $organization->name = $request->string('name')->trim()->toString();
        $organization->show_public_rosters = $request->boolean('show_public_rosters');
        $organization->primary_color = $request->filled('primary_color')
            ? mb_strtoupper($request->string('primary_color')->toString())
            : null;

        if ($request->hasFile('logo')) {
            $organization->logo_path = $request->file('logo')->store('logos', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $organization->logo_path = null;
        }

        $organization->save();

        if ($previousLogo !== null && $previousLogo !== $organization->logo_path) {
            Storage::disk('public')->delete($previousLogo);
        }

        return to_route('organization.profile.edit');
    }
}
