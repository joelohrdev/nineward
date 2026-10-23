<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ExtendTrialRequest;
use App\Models\Organization;
use App\Models\PlatformAuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class OrganizationTrialController extends Controller
{
    /**
     * Extend an organization's free trial, counting from today if it already ended.
     */
    public function update(ExtendTrialRequest $request, Organization $organization): RedirectResponse
    {
        $days = $request->integer('days');

        DB::transaction(function () use ($request, $organization, $days): void {
            $startsFrom = $organization->trial_ends_at?->isFuture() ? $organization->trial_ends_at : now();

            $organization->forceFill(['trial_ends_at' => $startsFrom->addDays($days)])->save();

            PlatformAuditLog::record($request->user(), 'trial.extended', $organization, $request->string('reason')->toString(), [
                'days' => $days,
                'trial_ends_at' => $organization->trial_ends_at?->toIso8601String(),
            ]);
        });

        return back();
    }
}
