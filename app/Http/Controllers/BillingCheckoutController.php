<?php

namespace App\Http\Controllers;

use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class BillingCheckoutController extends Controller
{
    /**
     * Send the owner to Stripe Checkout to start the subscription.
     */
    public function store(CurrentOrganization $currentOrganization): Response
    {
        $organization = $currentOrganization->get();
        abort_if($organization === null, 404);

        Gate::authorize('manageBilling', $organization);

        if ($organization->subscribed()) {
            return to_route('billing.show');
        }

        $checkout = $organization
            ->newSubscription('default', config()->string('services.stripe.price'))
            ->checkout([
                'success_url' => route('billing.show', ['checkout' => 'complete']),
                'cancel_url' => route('billing.show'),
            ]);

        return Inertia::location($checkout->asStripeCheckoutSession()->url);
    }
}
