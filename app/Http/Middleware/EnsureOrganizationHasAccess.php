<?php

namespace App\Http\Middleware;

use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends members of an organization whose trial or subscription has ended to
 * the billing page. Billing routes don't use this middleware, so a lapsed
 * organization can always resubscribe.
 */
class EnsureOrganizationHasAccess
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $this->currentOrganization->get();

        if ($organization === null
            || $organization->hasAccess()
            || $this->currentOrganization->isSupportSession()) {
            return $next($request);
        }

        return to_route('billing.show');
    }
}
