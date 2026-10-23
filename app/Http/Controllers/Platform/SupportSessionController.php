<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreSupportSessionRequest;
use App\Models\Organization;
use App\Models\PlatformAuditLog;
use App\Support\SupportSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportSessionController extends Controller
{
    /**
     * Enter an organization to see exactly what its members see.
     */
    public function store(StoreSupportSessionRequest $request, Organization $organization, SupportSession $supportSession): RedirectResponse
    {
        PlatformAuditLog::record($request->user(), 'support.started', $organization, $request->string('reason')->toString());

        $supportSession->start($organization);

        return to_route('dashboard');
    }

    public function destroy(Request $request, SupportSession $supportSession): RedirectResponse
    {
        $organizationId = $supportSession->organizationId();
        $organization = $organizationId === null ? null : Organization::find($organizationId);

        if ($organization !== null) {
            PlatformAuditLog::record($request->user(), 'support.ended', $organization);
        }

        $supportSession->end();

        return $organization === null
            ? to_route('platform.organizations.index')
            : to_route('platform.organizations.show', $organization);
    }
}
