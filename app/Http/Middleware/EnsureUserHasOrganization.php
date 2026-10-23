<?php

namespace App\Http\Middleware;

use App\Support\CurrentOrganization;
use App\Support\GuardianAccounts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends users who don't belong to an organization yet back to the invitation
 * or claim link they were following, to their players if they're a parent,
 * or to create an organization of their own.
 */
class EnsureUserHasOrganization
{
    /**
     * An invitation or claim link the user opened but hasn't finished, so they
     * come back to it after signing up and verifying their email.
     */
    public const PENDING_LINK_SESSION_KEY = 'pending_link_url';

    public function __construct(
        private CurrentOrganization $currentOrganization,
        private GuardianAccounts $guardianAccounts,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->currentOrganization->id() === null) {
            $pendingLink = $request->session()->get(self::PENDING_LINK_SESSION_KEY);

            if (is_string($pendingLink)) {
                return redirect()->to($pendingLink);
            }

            $user = $request->user();

            return $user !== null && $this->guardianAccounts->hasClaimed($user)
                ? to_route('my-players.index')
                : to_route('organizations.create');
        }

        return $next($request);
    }
}
