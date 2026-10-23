<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Support\CurrentOrganization;
use App\Support\SupportSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the organization the signed-in user is working in. The stored
 * choice is only honored while the user is still a member of it, unless a
 * platform admin has entered the organization to provide support.
 */
class ResolveCurrentOrganization
{
    public function __construct(
        private CurrentOrganization $currentOrganization,
        private SupportSession $supportSession,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->is_platform_admin && ($supported = $this->supportedOrganization()) !== null) {
            $this->currentOrganization->setForSupport($supported);
        } elseif (($organization = $this->resolveFor($user)) !== null) {
            $this->currentOrganization->set($organization);
        }

        return $next($request);
    }

    private function supportedOrganization(): ?Organization
    {
        $organizationId = $this->supportSession->organizationId();

        return $organizationId === null ? null : Organization::find($organizationId);
    }

    private function resolveFor(User $user): ?Organization
    {
        $organization = $user->current_organization_id === null
            ? null
            : $user->organizations()->whereKey($user->current_organization_id)->first();

        if ($organization !== null) {
            return $organization;
        }

        $organization = $user->organizations()
            ->orderBy('organization_user.created_at')
            ->orderBy('organizations.id')
            ->first();

        if ($organization !== null) {
            $user->switchOrganization($organization);
        }

        return $organization;
    }
}
