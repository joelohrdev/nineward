<?php

namespace App\Actions\Submissions;

use App\Enums\SubmissionStatus;
use App\Models\Player;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResolveSubmissionReview
{
    /**
     * Settle a possible duplicate. "Same player" moves the registration and
     * its guardian onto the existing player and removes the copy this
     * registration created. "Different player" keeps both.
     */
    public function handle(Submission $submission, User $reviewer, bool $isSamePlayer): void
    {
        DB::transaction(function () use ($submission, $reviewer, $isSamePlayer): void {
            $submission = Submission::query()->whereKey($submission->id)->lockForUpdate()->firstOrFail();

            if ($submission->status !== SubmissionStatus::NeedsReview) {
                throw ValidationException::withMessages(['resolution' => 'Someone already reviewed this registration.']);
            }

            if ($isSamePlayer) {
                $this->merge($submission);
            }

            $submission->status = SubmissionStatus::Processed;
            $submission->reviewed_at = now();
            $submission->reviewer()->associate($reviewer);
            $submission->save();
        });
    }

    private function merge(Submission $submission): void
    {
        $copy = $submission->player;
        $existing = $submission->possibleDuplicate;

        if ($copy === null || $existing === null) {
            throw ValidationException::withMessages(['resolution' => 'One of the players was removed. Mark this as a different player instead.']);
        }

        if ($copy->rosterEntries()->exists()) {
            throw ValidationException::withMessages(['resolution' => "{$copy->fullName()} is already on a team. Take them off it first."]);
        }

        foreach ($copy->guardians as $guardian) {
            $existing->guardians()->syncWithoutDetaching([
                $guardian->id => ['relationship' => $guardian->pivot?->getAttribute('relationship')],
            ]);
        }

        Submission::query()->where('player_id', $copy->id)->update(['player_id' => $existing->id]);
        $submission->player()->associate($existing);

        if (! $copy->submissions()->exists()) {
            Player::query()->whereKey($copy->id)->forceDelete();
        }
    }
}
