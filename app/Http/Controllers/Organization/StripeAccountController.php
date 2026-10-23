<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Payments\ConnectStripeAccount;
use App\Http\Controllers\Controller;
use App\Support\CurrentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Connecting the organization's own Stripe account, which registration fees
 * are paid straight into.
 */
class StripeAccountController extends Controller
{
    public function __construct(
        private CurrentOrganization $currentOrganization,
        private ConnectStripeAccount $connectStripeAccount,
    ) {}

    /**
     * Start or continue Stripe onboarding.
     */
    public function store(): Response
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('managePayments', $organization);

        return Inertia::location($this->connectStripeAccount->handle($organization));
    }

    /**
     * Where Stripe sends the owner back after onboarding.
     */
    public function return(): RedirectResponse
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('managePayments', $organization);

        if ($organization->stripeConnectAccount !== null) {
            $this->connectStripeAccount->syncStatus($organization->stripeConnectAccount);
        }

        return to_route('organization.payments.show');
    }

    /**
     * Stripe's onboarding links expire. This hands out a fresh one.
     */
    public function refresh(): RedirectResponse
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('managePayments', $organization);

        return redirect()->away($this->connectStripeAccount->handle($organization));
    }

    /**
     * Stop taking registration fees. The Stripe account itself is untouched.
     */
    public function destroy(): RedirectResponse
    {
        $organization = $this->currentOrganization->get();
        Gate::authorize('managePayments', $organization);

        $organization->stripeConnectAccount?->forceFill(['disconnected_at' => now()])->save();

        return to_route('organization.payments.show');
    }
}
