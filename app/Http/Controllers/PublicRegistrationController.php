<?php

namespace App\Http\Controllers;

use App\Actions\Payments\StartRegistrationCheckout;
use App\Actions\Submissions\ProcessSubmission;
use App\Enums\FormStatus;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Form;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class PublicRegistrationController extends Controller
{
    /**
     * The registration form families fill in. No account needed.
     */
    public function show(Organization $organization, Form $form): Response
    {
        $this->ensurePublished($form);
        $form->load(['currentVersion', 'season:id,name,is_registration_open']);

        return Inertia::render('register/show', [
            'organization' => $this->organizationSummary($organization),
            'form' => [
                'title' => $form->title,
                'slug' => $form->slug,
                'seasonName' => $form->season?->name,
                'isOpen' => $this->isAccepting($organization, $form),
                'priceCents' => $form->price_cents,
            ],
            'fields' => $form->currentVersion->schema['fields'] ?? [],
        ]);
    }

    public function store(
        StoreSubmissionRequest $request,
        Organization $organization,
        Form $form,
        ProcessSubmission $processSubmission,
        StartRegistrationCheckout $startCheckout,
    ): SymfonyResponse {
        $this->ensurePublished($form);
        abort_unless($this->isAccepting($organization, $form), 410);

        // Pretend bots succeeded so they don't learn to dodge the trap.
        if ($request->isFromBot()) {
            return to_route('registration.thanks', [$organization->slug, $form->slug]);
        }

        $user = $request->user();
        $submission = $processSubmission->handle($form, $request->answers(), $user instanceof User ? $user : null);
        $request->session()->put('registered_player', $submission->player?->first_name);

        if (! $form->chargesFee()) {
            return to_route('registration.thanks', [$organization->slug, $form->slug]);
        }

        try {
            $checkoutUrl = $startCheckout->forNewSubmission($organization, $submission);
        } catch (Throwable $exception) {
            // The registration is saved; the family can pay from the link on the next page.
            report($exception);
            $checkoutUrl = null;
        }

        $request->session()->put('registered_payment_id', $submission->payment()->value('id'));

        return $checkoutUrl === null
            ? to_route('registration.thanks', [$organization->slug, $form->slug, 'checkout' => 'unavailable'])
            : Inertia::location($checkoutUrl);
    }

    public function thanks(Request $request, Organization $organization, Form $form): Response
    {
        $this->ensurePublished($form);

        $paymentId = $request->session()->get('registered_payment_id');
        $payment = is_int($paymentId) ? Payment::find($paymentId) : null;

        return Inertia::render('register/thanks', [
            'organization' => $this->organizationSummary($organization),
            'formTitle' => $form->title,
            'playerName' => $request->session()->get('registered_player'),
            'checkout' => $request->string('checkout')->toString() ?: null,
            'payment' => $payment === null ? null : [
                'amountCents' => $payment->amount_cents,
                'status' => $payment->status->value,
                'payUrl' => $payment->status->isOutstanding() ? $payment->payUrl($organization) : null,
            ],
        ]);
    }

    /**
     * Drafts don't exist as far as the public is concerned.
     */
    private function ensurePublished(Form $form): void
    {
        abort_if($form->current_version_id === null || $form->status === FormStatus::Draft, 404);
    }

    /**
     * Open forms on open seasons take registrations. A form with a fee also
     * needs the organization's Stripe account to be connected.
     */
    private function isAccepting(Organization $organization, Form $form): bool
    {
        return $form->status === FormStatus::Published
            && ($form->season === null || $form->season->is_registration_open)
            && (! $form->chargesFee() || $organization->canAcceptPayments());
    }

    /**
     * @return array{name: string, slug: string, color: string|null, logoUrl: string|null}
     */
    private function organizationSummary(Organization $organization): array
    {
        return [
            'name' => $organization->name,
            'slug' => $organization->slug,
            'color' => $organization->primary_color,
            'logoUrl' => $organization->logoUrl(),
        ];
    }
}
