<?php

namespace App\Enums;

enum ThrowingArm: string
{
    case Right = 'R';
    case Left = 'L';

    public function label(): string
    {
        return match ($this) {
            self::Right => 'Right',
            self::Left => 'Left',
        };
    }
}
