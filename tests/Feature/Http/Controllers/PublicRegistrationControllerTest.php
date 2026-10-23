<?php

use App\Enums\FormStatus;
use App\Models\Form;
use App\Models\Guardian;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Notification::fake());

test('families can open a published form without an account', function () {
    $organization = Organization::factory()->create(['slug' => 'cary-trojans']);
    $form = publishedRegistrationForm($organization);

    $this->get(route('registration.show', ['cary-trojans', $form->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('register/show')
            ->where('organization.name', $organization->name)
            ->where('form.isOpen', true)
            ->has('fields', 6));
});

test('drafts and other organizations\' forms are not found', function () {
    $organization = Organization::factory()->create();
    $draft = Form::factory()->for($organization)->create();
    $theirs = publishedRegistrationForm(Organization::factory()->create());

    $this->get(route('registration.show', [$organization->slug, $draft->slug]))->assertNotFound();
    $this->get(route('registration.show', [$organization->slug, $theirs->slug]))->assertNotFound();
    $this->get(route('registration.show', ['no-such-org', $draft->slug]))->assertNotFound();
});

test('a closed form, or one whose season has closed registration, shows as closed and refuses answers', function (Closure $close) {
    $organization = Organization::factory()->create();
    $form = publishedRegistrationForm($organization, formAttributes: [
        'season_id' => Season::factory()->for($organization)->create(['is_registration_open' => true])->id,
    ]);
    $close($form);

    $this->get(route('registration.show', [$organization->slug, $form->slug]))
        ->assertInertia(fn (Assert $page) => $page->where('form.isOpen', false));

    $this->post(route('registration.store', [$organization->slug, $form->slug]), ['answers' => registrationAnswers()])
        ->assertStatus(410);

    expect(Submission::withoutGlobalScopes()->count())->toBe(0);
})->with([
    'form closed' => [fn (Form $form) => $form->forceFill(['status' => FormStatus::Closed])->save()],
    'season registration closed' => [fn (Form $form) => Season::withoutGlobalScopes()->whereKey($form->season_id)->update(['is_registration_open' => false])],
]);

test('a registration creates the player and guardian in the form\'s organization', function () {
    $organization = Organization::factory()->create();
    $form = publishedRegistrationForm($organization, [
        ['id' => 'f_bats', 'type' => 'select', 'label' => 'Bats', 'maps_to' => 'player.bats'],
        ['id' => 'f_allergy', 'type' => 'short_text', 'label' => 'Allergies?'],
    ]);

    $this->post(route('registration.store', [$organization->slug, $form->slug]), [
        'answers' => registrationAnswers(['f_bats' => 'Left', 'f_allergy' => 'Peanuts']),
    ])->assertRedirect(route('registration.thanks', [$organization->slug, $form->slug]));

    $player = Player::withoutGlobalScopes()->sole();
    $guardian = Guardian::withoutGlobalScopes()->sole();
    $submission = Submission::withoutGlobalScopes()->sole();
    expect($player->organization_id)->toBe($organization->id)
        ->and($player->fullName())->toBe('Caleb Ortiz')
        ->and($player->bats->value)->toBe('L')
        ->and($guardian->email)->toBe('maria@example.com')
        ->and($submission->player_id)->toBe($player->id)
        ->and($submission->form_version_id)->toBe($form->current_version_id)
        ->and($submission->data['f_allergy'])->toBe('Peanuts');
});

test('answers are stored encrypted', function () {
    $organization = Organization::factory()->create();
    $form = publishedRegistrationForm($organization, [['id' => 'f_allergy', 'type' => 'short_text', 'label' => 'Allergies?']]);

    $this->post(route('registration.store', [$organization->slug, $form->slug]), [
        'answers' => registrationAnswers(['f_allergy' => 'Peanuts']),
    ]);

    expect(DB::table('submissions')->value('data'))->not->toContain('Peanuts');
});

test('answers are validated against the form\'s questions', function () {
    $organization = Organization::factory()->create();
    $form = publishedRegistrationForm($organization, [
        ['id' => 'f_size', 'type' => 'select', 'label' => 'Jersey size', 'options' => ['YS', 'YM', 'YL'], 'required' => true],
        ['id' => 'f_phone', 'type' => 'phone', 'label' => 'Emergency phone'],
    ]);

    $this->post(route('registration.store', [$organization->slug, $form->slug]), [
        'answers' => registrationAnswers(['f_pfirst' => '', 'f_gemail' => 'not-an-email', 'f_size' => 'XXL', 'f_pdob' => now()->addDay()->toDateString(), 'f_phone' => 'call me']),
    ])->assertSessionHasErrors([
        'answers.f_pfirst' => "The player's first name field is required.",
        'answers.f_gemail' => "The guardian's email field must be a valid email address.",
        'answers.f_size' => 'The selected jersey size is invalid.',
        'answers.f_pdob',
        'answers.f_phone',
    ]);

    expect(Submission::withoutGlobalScopes()->count())->toBe(0);
});

test('a filled-in honeypot pretends to succeed but saves nothing', function () {
    $organization = Organization::factory()->create();
    $form = publishedRegistrationForm($organization);

    $this->post(route('registration.store', [$organization->slug, $form->slug]), [
        'answers' => registrationAnswers(),
        'website' => 'http://spam.example',
    ])->assertRedirect(route('registration.thanks', [$organization->slug, $form->slug]));

    expect(Submission::withoutGlobalScopes()->count())->toBe(0)
        ->and(Player::withoutGlobalScopes()->count())->toBe(0);
});

test('submissions are rate limited per visitor', function () {
    $organization = Organization::factory()->create();
    $form = publishedRegistrationForm($organization);
    $url = route('registration.store', [$organization->slug, $form->slug]);

    foreach (range(1, 5) as $attempt) {
        $this->post($url, ['answers' => registrationAnswers(), 'website' => 'bot'])->assertRedirect();
    }

    $this->post($url, ['answers' => registrationAnswers()])->assertTooManyRequests();
    RateLimiter::clear('registrations');
});
