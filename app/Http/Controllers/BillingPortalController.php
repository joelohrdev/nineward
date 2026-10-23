<?php

namespace App\Http\Controllers;

use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class BillingPortalController extends Controller
{
    /**
     * Send the owner to Stripe's billing portal to manage cards, invoices and cancellation.
     */
    public function store(CurrentOrganization $currentOrganization): Response
    {
        $organization = $currentOrganization->get();
        abort_if($organization === null, 404);

        Gate::authorize('manageBilling', $organization);

        abort_unless($organization->hasStripeId(), 404);

        return Inertia::location($organization->billingPortalUrl(route('billing.show')));
    }
}
