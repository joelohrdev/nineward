<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function show(CurrentOrganization $currentOrganization): Response
    {
        $organization = $currentOrganization->get();
        abort_if($organization === null, 404);

        Gate::authorize('viewBilling', $organization);

        return Inertia::render('billing/show', [
            'billing' => $this->summarize($organization),
            'canManageBilling' => Gate::allows('manageBilling', $organization),
        ]);
    }

    /**
     * @return array{status: string, trialEndsAt: string|null, endsAt: string|null, hasStripeCustomer: bool, cardLastFour: string|null}
     */
    private function summarize(Organization $organization): array
    {
        $subscription = $organization->subscription();

        $status = match (true) {
            $subscription?->onGracePeriod() === true => 'cancelling',
            $subscription?->hasIncompletePayment() === true => 'payment_needed',
            $organization->subscribed() => 'active',
            $organization->onGenericTrial() => 'trial',
            default => 'ended',
        };

        return [
            'status' => $status,
            'trialEndsAt' => $organization->trial_ends_at?->toIso8601String(),
            'endsAt' => $subscription?->ends_at?->toIso8601String(),
            'hasStripeCustomer' => $organization->hasStripeId(),
            'cardLastFour' => $organization->pm_last_four,
        ];
    }
}
