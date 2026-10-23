<?php

namespace App\Enums;

/**
 * Which side a player bats from.
 */
enum Handedness: string
{
    case Right = 'R';
    case Left = 'L';
    case Switch = 'S';

    public function label(): string
    {
        return match ($this) {
            self::Right => 'Right',
            self::Left => 'Left',
            self::Switch => 'Switch',
        };
    }
}
