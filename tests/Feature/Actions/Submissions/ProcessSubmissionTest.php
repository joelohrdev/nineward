<?php

use App\Actions\Submissions\ProcessSubmission;
use App\Enums\OrganizationRole;
use App\Enums\SubmissionStatus;
use App\Models\Guardian;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\NewRegistrationSubmitted;
use App\Notifications\RegistrationReceived;
use App\Support\CurrentOrganization;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

function processFor(Organization $organization, array $answers)
{
    app(CurrentOrganization::class)->set($organization);
    $form = publishedRegistrationForm($organization);

    return app(ProcessSubmission::class)->handle($form, $answers);
}

test('the same guardian registering the same kid again reuses the player', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $guardian = Guardian::factory()->for($organization)->create(['email' => 'maria@example.com']);
    $player = Player::factory()->for($organization)->create(['first_name' => 'Caleb', 'last_name' => 'Ortiz', 'date_of_birth' => '2016-03-14']);
    $player->guardians()->attach($guardian);

    $submission = processFor($organization, registrationAnswers(['f_pfirst' => 'caleb', 'f_gemail' => 'MARIA@example.com']));

    expect($submission->player_id)->toBe($player->id)
        ->and($submission->status)->toBe(SubmissionStatus::Processed)
        ->and(Player::count())->toBe(1)
        ->and(Guardian::count())->toBe(1);
});

test('a sibling with the same guardian becomes a new player linked to that guardian', function () {
    $organization = Organization::factory()->create();
    processFor($organization, registrationAnswers());

    $sibling = app(ProcessSubmission::class)->handle(
        publishedRegistrationForm($organization),
        registrationAnswers(['f_pfirst' => 'Ava', 'f_pdob' => '2018-07-01']),
    );

    expect(Player::count())->toBe(2)
        ->and(Guardian::count())->toBe(1)
        ->and($sibling->status)->toBe(SubmissionStatus::Processed);
});

test('the same name and birthday from a different guardian is kept separate and flagged', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $existing = Player::factory()->for($organization)->create(['first_name' => 'Caleb', 'last_name' => 'Ortiz', 'date_of_birth' => '2016-03-14']);

    $submission = processFor($organization, registrationAnswers(['f_gemail' => 'dad@example.com']));

    expect($submission->status)->toBe(SubmissionStatus::NeedsReview)
        ->and($submission->possible_duplicate_player_id)->toBe($existing->id)
        ->and($submission->player_id)->not->toBe($existing->id)
        ->and(Player::count())->toBe(2);
});

test('existing player details are never overwritten', function () {
    $organization = Organization::factory()->create();
    app(CurrentOrganization::class)->set($organization);
    $guardian = Guardian::factory()->for($organization)->create(['email' => 'maria@example.com']);
    $player = Player::factory()->for($organization)->create(['first_name' => 'Caleb', 'last_name' => 'Ortiz', 'date_of_birth' => '2016-03-14', 'bats' => 'R']);
    $player->guardians()->attach($guardian);
    $form = publishedRegistrationForm($organization, [['id' => 'f_bats', 'type' => 'select', 'label' => 'Bats', 'maps_to' => 'player.bats']]);

    app(ProcessSubmission::class)->handle($form, registrationAnswers(['f_bats' => 'Left']));

    expect($player->refresh()->bats->value)->toBe('R');
});

test('emails the family and the organization\'s owners and admins', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->memberOf($organization, OrganizationRole::Admin)->create();
    $coach = User::factory()->memberOf($organization, OrganizationRole::Coach)->create();

    processFor($organization, registrationAnswers());

    Notification::assertSentTo(new AnonymousNotifiable, RegistrationReceived::class,
        fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'maria@example.com');
    Notification::assertSentTo([$organization->owner, $admin], NewRegistrationSubmitted::class);
    Notification::assertNotSentTo($coach, NewRegistrationSubmitted::class);
});

test('both notifications are dispatched after commit', function () {
    $form = publishedRegistrationForm(Organization::factory()->create());
    $player = Player::factory()->create(['organization_id' => $form->organization_id]);

    $submission = Submission::factory()->create(['form_id' => $form->id]);

    expect((new RegistrationReceived($submission))->afterCommit)->toBeTrue()
        ->and((new NewRegistrationSubmitted($submission, $player))->afterCommit)->toBeTrue();
});
