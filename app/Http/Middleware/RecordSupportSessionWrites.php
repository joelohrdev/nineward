<?php

namespace App\Http\Middleware;

use App\Models\PlatformAuditLog;
use App\Models\User;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs every change a platform admin makes while inside an organization.
 */
class RecordSupportSessionWrites
{
    public function __construct(private CurrentOrganization $currentOrganization) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if ($user instanceof User
            && ! $request->isMethodSafe()
            && $this->currentOrganization->isSupportSession()) {
            PlatformAuditLog::record($user, 'support.write', $this->currentOrganization->get(), metadata: [
                'method' => $request->method(),
                'route' => $request->route()?->getName(),
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
