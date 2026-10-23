<?php

use App\Enums\OrganizationRole;
use App\Enums\SubmissionStatus;
use App\Models\Guardian;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Submission;
use App\Models\User;
use App\Support\CurrentOrganization;
use Inertia\Testing\AssertableInertia as Assert;

test('lists registrations and filters to those needing review', function () {
    $organization = Organization::factory()->create();
    $form = publishedRegistrationForm($organization);
    Submission::factory()->create(['form_id' => $form->id]);
    Submission::factory()->needsReview()->create(['form_id' => $form->id]);
    Submission::factory()->create();

    $this->actingAs($organization->owner)
        ->get(route('submissions.index', ['status' => 'needs_review']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('submissions/index')
            ->has('submissions.data', 1)
            ->where('needsReviewCount', 1));
});

test('shows the answers against the version the family filled in', function () {
    $organization = Organization::factory()->create();
    $form = publishedRegistrationForm($organization, [['id' => 'f_allergy', 'type' => 'short_text', 'label' => 'Allergies?']]);
    $submission = Submission::factory()->create(['form_id' => $form->id, 'data' => registrationAnswers(['f_allergy' => 'Peanuts'])]);

    $this->actingAs($organization->owner)
        ->get(route('submissions.show', $submission))
        ->assertInertia(fn (Assert $page) => $page
            ->component('submissions/show')
            ->where('answers.2.answer', 'Mar 14, 2016')
            ->where('answers.6', ['label' => 'Allergies?', 'answer' => 'Peanuts', 'isSection' => false]));
});

test('confirming a duplicate moves the guardian to the existing player and removes the copy', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $existing = Player::factory()->for($organization)->create();
    $copy = Player::factory()->for($organization)->create();
    $guardian = Guardian::factory()->for($organization)->create();
    $copy->guardians()->attach($guardian, ['relationship' => 'Father']);
    $submission = Submission::factory()->needsReview()->create([
        'form_id' => publishedRegistrationForm($organization)->id,
        'player_id' => $copy->id,
        'guardian_id' => $guardian->id,
        'possible_duplicate_player_id' => $existing->id,
    ]);

    $this->actingAs($organization->owner)
        ->post(route('submissions.review.store', $submission), ['resolution' => 'same_player'])
        ->assertRedirect(route('submissions.show', $submission));

    $submission->refresh();
    expect($submission->status)->toBe(SubmissionStatus::Processed)
        ->and($submission->player_id)->toBe($existing->id)
        ->and($submission->reviewed_by)->toBe($organization->owner_id)
        ->and(Player::withTrashed()->find($copy->id))->toBeNull()
        ->and($existing->guardians()->sole()->pivot->relationship)->toBe('Father');
});

test('marking them as different players keeps both', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $copy = Player::factory()->for($organization)->create();
    $submission = Submission::factory()->needsReview()->create([
        'form_id' => publishedRegistrationForm($organization)->id,
        'player_id' => $copy->id,
        'possible_duplicate_player_id' => Player::factory()->for($organization)->create()->id,
    ]);

    $this->actingAs($organization->owner)
        ->post(route('submissions.review.store', $submission), ['resolution' => 'different_player']);

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Processed)
        ->and(Player::count())->toBe(2);
});

test('a reviewed registration cannot be reviewed again', function () {
    $organization = Organization::factory()->create();
    $submission = Submission::factory()->create(['form_id' => publishedRegistrationForm($organization)->id]);

    $this->actingAs($organization->owner)
        ->post(route('submissions.review.store', $submission), ['resolution' => 'same_player'])
        ->assertSessionHasErrors(['resolution' => 'Someone already reviewed this registration.']);
});

test('dismissing sets a registration aside', function () {
    $organization = Organization::factory()->create();
    $submission = Submission::factory()->create(['form_id' => publishedRegistrationForm($organization)->id]);

    $this->actingAs($organization->owner)
        ->post(route('submissions.dismissal.store', $submission))
        ->assertRedirect(route('submissions.index'));

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Dismissed);
});

test('coaches cannot see registrations', function () {
    $organization = Organization::factory()->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();

    $this->actingAs($coach)->get(route('submissions.index'))->assertForbidden();
});
