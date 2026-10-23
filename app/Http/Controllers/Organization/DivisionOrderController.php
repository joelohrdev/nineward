<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateDivisionOrderRequest;
use App\Models\Division;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DivisionOrderController extends Controller
{
    /**
     * Save the order divisions are listed in.
     */
    public function update(UpdateDivisionOrderRequest $request, CurrentOrganization $currentOrganization): RedirectResponse
    {
        Gate::authorize('manageSettings', $currentOrganization->get());

        $divisionIds = $request->divisionIds();

        DB::transaction(function () use ($divisionIds): void {
            foreach ($divisionIds as $position => $divisionId) {
                Division::whereKey($divisionId)->update(['display_order' => $position + 1]);
            }
        });

        return to_route('organization.divisions.index');
    }
}
