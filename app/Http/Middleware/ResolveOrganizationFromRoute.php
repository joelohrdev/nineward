<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For public pages, the organization comes from the URL rather than the
 * visitor's account. Setting it before route bindings resolve means the
 * usual tenant scope keeps every lookup inside that organization.
 */
class ResolveOrganizationFromRoute
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('organization');

        $organization = is_string($slug)
            ? Organization::query()->where('slug', $slug)->first()
            : null;

        abort_if($organization === null, 404);

        $this->currentOrganization->set($organization);

        return $next($request);
    }
}
