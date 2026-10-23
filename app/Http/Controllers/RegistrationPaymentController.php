<?php

namespace App\Http\Controllers;

use App\Actions\Payments\StartRegistrationCheckout;
use App\Models\Organization;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;

class RegistrationPaymentController extends Controller
{
    /**
     * The "pay now" link families get when a fee is still owed. It opens a
     * fresh Stripe Checkout each time.
     */
    public function show(Organization $organization, Payment $payment, StartRegistrationCheckout $startCheckout): RedirectResponse
    {
        $payment->load('submission.form');
        $form = $payment->submission->form;

        if (! $payment->status->isOutstanding()) {
            return to_route('registration.thanks', [$organization->slug, $form->slug, 'checkout' => 'paid']);
        }

        return redirect()->away($startCheckout->checkout($organization, $payment));
    }
}
