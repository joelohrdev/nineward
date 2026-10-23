<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingsController extends Controller
{
    /**
     * Whether the organization can collect registration fees, and how to set it up.
     */
    public function show(CurrentOrganization $currentOrganization): Response
    {
        $organization = $currentOrganization->get();
        Gate::authorize('manageSettings', $organization);

        $account = $organization->stripeConnectAccount;

        return Inertia::render('organization/payments', [
            'account' => $account === null ? null : [
                'stripeAccountId' => $account->stripe_account_id,
                'chargesEnabled' => $account->charges_enabled,
                'payoutsEnabled' => $account->payouts_enabled,
                'detailsSubmitted' => $account->details_submitted,
                'isDisconnected' => $account->disconnected_at !== null,
            ],
            'canManagePayments' => Gate::allows('managePayments', $organization),
        ]);
    }
}
