<?php

namespace App\Forms;

use App\Enums\Handedness;
use App\Enums\Position;
use App\Enums\ThrowingArm;

/**
 * Where a form answer is copied when a registration is processed. Answers
 * with a mapping land in real player and guardian columns, so they can be
 * searched and filtered; everything else stays in the submission.
 */
enum FieldMapping: string
{
    case PlayerFirstName = 'player.first_name';
    case PlayerLastName = 'player.last_name';
    case PlayerDateOfBirth = 'player.date_of_birth';
    case PlayerGraduationYear = 'player.graduation_year';
    case PlayerBats = 'player.bats';
    case PlayerThrows = 'player.throws';
    case PlayerPosition = 'player.primary_position';
    case GuardianFirstName = 'guardian.first_name';
    case GuardianLastName = 'guardian.last_name';
    case GuardianEmail = 'guardian.email';
    case GuardianPhone = 'guardian.phone';
    case GuardianRelationship = 'guardian.relationship';

    /**
     * Every registration form has these, always required, so a submission
     * can always become a player with a guardian.
     *
     * @return list<self>
     */
    public static function core(): array
    {
        return [
            self::PlayerFirstName,
            self::PlayerLastName,
            self::PlayerDateOfBirth,
            self::GuardianFirstName,
            self::GuardianLastName,
            self::GuardianEmail,
        ];
    }

    public function isCore(): bool
    {
        return in_array($this, self::core(), true);
    }

    public function type(): FieldType
    {
        return match ($this) {
            self::PlayerDateOfBirth => FieldType::Date,
            self::PlayerGraduationYear => FieldType::Number,
            self::PlayerBats, self::PlayerThrows, self::PlayerPosition => FieldType::Select,
            self::GuardianEmail => FieldType::Email,
            self::GuardianPhone => FieldType::Phone,
            default => FieldType::ShortText,
        };
    }

    public function defaultLabel(): string
    {
        return match ($this) {
            self::PlayerFirstName => "Player's first name",
            self::PlayerLastName => "Player's last name",
            self::PlayerDateOfBirth => "Player's date of birth",
            self::PlayerGraduationYear => 'High school graduation year',
            self::PlayerBats => 'Bats',
            self::PlayerThrows => 'Throws',
            self::PlayerPosition => 'Primary position',
            self::GuardianFirstName => "Parent or guardian's first name",
            self::GuardianLastName => "Parent or guardian's last name",
            self::GuardianEmail => "Parent or guardian's email",
            self::GuardianPhone => "Parent or guardian's phone",
            self::GuardianRelationship => 'Relationship to the player',
        };
    }

    /**
     * The fixed answers for mapped dropdowns, as value => label.
     *
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = match ($this) {
            self::PlayerBats => Handedness::cases(),
            self::PlayerThrows => ThrowingArm::cases(),
            self::PlayerPosition => Position::cases(),
            default => [],
        };

        $labels = [];

        foreach ($options as $option) {
            $labels[$option->value] = $option->label();
        }

        return $labels;
    }
}
