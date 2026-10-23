<?php

namespace App\Http\Controllers;

use App\Actions\Payments\HandleConnectWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Events from organizations' connected Stripe accounts. This endpoint has its
 * own signing secret, separate from the platform's billing webhook.
 */
class StripeConnectWebhookController extends Controller
{
    public function __invoke(Request $request, HandleConnectWebhookEvent $handleEvent): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                config()->string('services.stripe.connect_webhook_secret'),
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response('Invalid signature.', 400);
        }

        $handleEvent->handle($event->toArray());

        return response('OK');
    }
}
