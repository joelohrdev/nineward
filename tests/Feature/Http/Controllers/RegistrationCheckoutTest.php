<?php

use App\Enums\FormStatus;
use App\Enums\PaymentStatus;
use App\Models\Form;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Notification::fake());

test('a form with a fee sends the family to Checkout on the organization\'s own account', function () {
    $gateway = fakePaymentGateway();
    $organization = Organization::factory()->create();
    $account = connectStripe($organization);
    $form = publishedRegistrationForm($organization, formAttributes: ['price_cents' => 15000]);

    $this->post(route('registration.store', [$organization->slug, $form->slug]), ['answers' => registrationAnswers()])
        ->assertRedirect('https://checkout.stripe.test/cs_test_1');

    $payment = Payment::withoutGlobalScopes()->sole();
    expect($payment->amount_cents)->toBe(15000)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->stripe_checkout_session_id)->toBe('cs_test_1')
        ->and($gateway->checkouts[0]['accountId'])->toBe($account->stripe_account_id)
        ->and($gateway->checkouts[0]['amountCents'])->toBe(15000)
        ->and($gateway->checkouts[0]['customerEmail'])->toBe('maria@example.com')
        ->and($gateway->checkouts[0]['metadata'])->toBe(['payment_id' => (string) $payment->id]);
});

test('the fee is copied when the family registers, so later price changes don\'t affect it', function () {
    fakePaymentGateway();
    $organization = Organization::factory()->create();
    connectStripe($organization);
    $form = publishedRegistrationForm($organization, formAttributes: ['price_cents' => 15000]);

    $this->post(route('registration.store', [$organization->slug, $form->slug]), ['answers' => registrationAnswers()]);
    $form->forceFill(['price_cents' => 20000])->save();

    expect(Payment::withoutGlobalScopes()->sole()->amount_cents)->toBe(15000);
});

test('free forms skip payment', function () {
    $gateway = fakePaymentGateway();
    $organization = Organization::factory()->create();
    $form = publishedRegistrationForm($organization);

    $this->post(route('registration.store', [$organization->slug, $form->slug]), ['answers' => registrationAnswers()])
        ->assertRedirect(route('registration.thanks', [$organization->slug, $form->slug]));

    expect($gateway->checkouts)->toBe([])->and(Payment::withoutGlobalScopes()->count())->toBe(0);
});

test('a form with a fee is closed while Stripe is disconnected', function () {
    fakePaymentGateway();
    $organization = Organization::factory()->create();
    connectStripe($organization, ['disconnected_at' => now()]);
    $form = publishedRegistrationForm($organization, formAttributes: ['price_cents' => 15000]);

    $this->get(route('registration.show', [$organization->slug, $form->slug]))
        ->assertInertia(fn (Assert $page) => $page->where('form.isOpen', false));
    $this->post(route('registration.store', [$organization->slug, $form->slug]), ['answers' => registrationAnswers()])
        ->assertStatus(410);
});

test('if Stripe is unreachable the registration is kept and the family can pay later', function () {
    $gateway = fakePaymentGateway();
    $gateway->failCheckout = true;
    $organization = Organization::factory()->create();
    connectStripe($organization);
    $form = publishedRegistrationForm($organization, formAttributes: ['price_cents' => 15000]);

    $this->post(route('registration.store', [$organization->slug, $form->slug]), ['answers' => registrationAnswers()])
        ->assertRedirect(route('registration.thanks', [$organization->slug, $form->slug, 'checkout' => 'unavailable']));

    $this->get(route('registration.thanks', [$organization->slug, $form->slug, 'checkout' => 'unavailable']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('payment.amountCents', 15000)
            ->where('payment.payUrl', fn ($url) => is_string($url)));
});

test('the pay-later link opens a new Checkout until the fee is paid', function () {
    $gateway = fakePaymentGateway();
    $organization = Organization::factory()->create();
    connectStripe($organization);
    $form = publishedRegistrationForm($organization, formAttributes: ['price_cents' => 15000]);
    $this->post(route('registration.store', [$organization->slug, $form->slug]), ['answers' => registrationAnswers()]);
    $payment = Payment::withoutGlobalScopes()->sole();
    $payment->forceFill(['status' => PaymentStatus::Expired])->save();

    $this->get($payment->payUrl($organization))->assertRedirect('https://checkout.stripe.test/cs_test_2');
    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending);

    $payment->forceFill(['status' => PaymentStatus::Paid])->save();
    $this->get($payment->payUrl($organization))
        ->assertRedirect(route('registration.thanks', [$organization->slug, $form->slug, 'checkout' => 'paid']));
});

test('the pay-later link needs a valid signature', function () {
    $organization = Organization::factory()->create();
    $payment = Payment::factory()->create(['submission_id' => Submission::factory()->create(['form_id' => publishedRegistrationForm($organization)->id])->id]);

    $this->get(route('registration-payments.show', [$organization->slug, $payment->id]))->assertForbidden();
});

test('a form with a fee can\'t be published until Stripe can take payments', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create(['price_cents' => 15000]);

    $this->actingAs($organization->owner)
        ->post(route('forms.publication.store', $form))
        ->assertSessionHasErrors(['form' => 'Connect your Stripe account before opening a form with a fee.']);

    expect($form->refresh()->status)->toBe(FormStatus::Draft);

    connectStripe($organization);
    $this->actingAs($organization->owner)->post(route('forms.publication.store', $form))->assertSessionHasNoErrors();
    expect($form->refresh()->status)->toBe(FormStatus::Published);
});

test('the fee is set in dollars on the form details', function () {
    $organization = Organization::factory()->create();
    $form = Form::factory()->for($organization)->create();

    $this->actingAs($organization->owner)
        ->put(route('forms.update', $form), ['title' => $form->title, 'price' => '149.99'])
        ->assertSessionHasNoErrors();

    expect($form->refresh()->price_cents)->toBe(14999);
});
