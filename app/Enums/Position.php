<?php

namespace App\Enums;

/**
 * Fielding positions, stored as their scorebook abbreviations.
 */
enum Position: string
{
    case Pitcher = 'P';
    case Catcher = 'C';
    case FirstBase = '1B';
    case SecondBase = '2B';
    case ThirdBase = '3B';
    case Shortstop = 'SS';
    case LeftField = 'LF';
    case CenterField = 'CF';
    case RightField = 'RF';
    case Utility = 'UT';

    public function label(): string
    {
        return match ($this) {
            self::Pitcher => 'Pitcher',
            self::Catcher => 'Catcher',
            self::FirstBase => 'First base',
            self::SecondBase => 'Second base',
            self::ThirdBase => 'Third base',
            self::Shortstop => 'Shortstop',
            self::LeftField => 'Left field',
            self::CenterField => 'Center field',
            self::RightField => 'Right field',
            self::Utility => 'Utility',
        };
    }
}
