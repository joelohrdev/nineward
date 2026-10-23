<?php

namespace App\Actions\Submissions;

use App\Actions\Guardians\FindOrCreateGuardian;
use App\Enums\OrganizationRole;
use App\Enums\SubmissionStatus;
use App\Forms\FieldMapping;
use App\Forms\SubmissionAnswers;
use App\Models\Form;
use App\Models\Player;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\NewRegistrationSubmitted;
use App\Notifications\RegistrationReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ProcessSubmission
{
    public function __construct(private FindOrCreateGuardian $findOrCreateGuardian) {}

    /**
     * Save a family's registration and turn it into player and guardian records.
     *
     * Players are matched conservatively: the same guardian, first name and
     * birthday means the same kid. A different guardian registering a kid
     * with the same name and birthday gets a new player, and the submission
     * is flagged so an admin decides. Merging the wrong kids is worse than a
     * duplicate.
     *
     * @param  array<string, string|null>  $answers  keyed by field ID
     */
    public function handle(Form $form, array $answers, ?User $user = null): Submission
    {
        $version = $form->currentVersion()->firstOrFail();
        $read = new SubmissionAnswers($version, $answers);

        $submission = DB::transaction(function () use ($form, $version, $answers, $read, $user): Submission {
            $guardian = $this->findOrCreateGuardian->handle([
                'first_name' => (string) $read->mapped(FieldMapping::GuardianFirstName),
                'last_name' => (string) $read->mapped(FieldMapping::GuardianLastName),
                'email' => (string) $read->mapped(FieldMapping::GuardianEmail),
                'phone' => $read->mapped(FieldMapping::GuardianPhone),
            ]);

            $firstName = (string) $read->mapped(FieldMapping::PlayerFirstName);
            $lastName = (string) $read->mapped(FieldMapping::PlayerLastName);
            $dateOfBirth = (string) $read->mapped(FieldMapping::PlayerDateOfBirth);

            $player = $guardian->players()
                ->whereRaw('lower(first_name) = ?', [mb_strtolower($firstName)])
                ->whereDate('date_of_birth', $dateOfBirth)
                ->first();

            $possibleDuplicate = null;

            if ($player === null) {
                $possibleDuplicate = Player::query()
                    ->whereRaw('lower(first_name) = ?', [mb_strtolower($firstName)])
                    ->whereRaw('lower(last_name) = ?', [mb_strtolower($lastName)])
                    ->whereDate('date_of_birth', $dateOfBirth)
                    ->first();

                $player = Player::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $dateOfBirth,
                ]);
            }

            $this->fillMissingDetails($player, $read);

            $player->guardians()->syncWithoutDetaching([
                $guardian->id => ['relationship' => $read->mapped(FieldMapping::GuardianRelationship)],
            ]);

            $submission = new Submission([
                'data' => $answers,
                'status' => $possibleDuplicate === null ? SubmissionStatus::Processed : SubmissionStatus::NeedsReview,
                'review_reason' => $possibleDuplicate === null
                    ? null
                    : 'A player with the same name and birthday is already in your list, registered by a different parent or guardian.',
            ]);
            $submission->form()->associate($form);
            $submission->formVersion()->associate($version);
            $submission->guardian()->associate($guardian);
            $submission->player()->associate($player);
            $submission->possibleDuplicate()->associate($possibleDuplicate);
            $submission->forceFill(['user_id' => $user?->id]);
            $submission->save();

            return $submission;
        });

        $this->notify($form, $submission);

        return $submission;
    }

    /**
     * Copy mapped answers onto the player, without overwriting what the
     * organization already has on file.
     */
    private function fillMissingDetails(Player $player, SubmissionAnswers $read): void
    {
        $details = [
            'graduation_year' => FieldMapping::PlayerGraduationYear,
            'bats' => FieldMapping::PlayerBats,
            'throws' => FieldMapping::PlayerThrows,
            'primary_position' => FieldMapping::PlayerPosition,
        ];

        foreach ($details as $attribute => $mapping) {
            $answer = $read->mapped($mapping);

            if ($answer !== null && $player->getAttribute($attribute) === null) {
                $player->setAttribute($attribute, $answer);
            }
        }

        $player->save();
    }

    private function notify(Form $form, Submission $submission): void
    {
        $guardian = $submission->guardian;
        $player = $submission->player;

        if ($guardian === null || $player === null) {
            return;
        }

        Notification::route('mail', $guardian->email)
            ->notify(new RegistrationReceived($submission));

        $managers = $form->organization->members()
            ->wherePivotIn('role', [OrganizationRole::Owner->value, OrganizationRole::Admin->value])
            ->get();

        Notification::send($managers, new NewRegistrationSubmitted($submission, $player));
    }
}
