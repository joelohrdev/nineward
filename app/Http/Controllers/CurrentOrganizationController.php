<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCurrentOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

class CurrentOrganizationController extends Controller
{
    /**
     * Switch the organization the user is working in.
     */
    public function update(UpdateCurrentOrganizationRequest $request): RedirectResponse
    {
        $organization = Organization::findOrFail($request->integer('organization_id'));

        $request->user()->switchOrganization($organization);

        return to_route('dashboard');
    }
}
