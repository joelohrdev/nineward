<?php

namespace App\Http\Controllers\Players;

use App\Http\Controllers\Controller;
use App\Http\Requests\Players\SaveGuardianRequest;
use App\Models\Guardian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class GuardianController extends Controller
{
    /**
     * Correct a guardian's contact details everywhere they appear.
     */
    public function update(SaveGuardianRequest $request, Guardian $guardian): RedirectResponse
    {
        Gate::authorize('update', $guardian);

        $guardian->update($request->guardianDetails());

        return back();
    }
}
