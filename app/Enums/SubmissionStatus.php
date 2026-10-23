<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    /** Turned into a player and guardian records with nothing left to check. */
    case Processed = 'processed';

    /** Saved, but something looked off and an admin should look. */
    case NeedsReview = 'needs_review';

    /** Set aside by an admin, for example as spam or a test. */
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Processed => 'Received',
            self::NeedsReview => 'Needs review',
            self::Dismissed => 'Dismissed',
        };
    }
}
