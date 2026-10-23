<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Coach = 'coach';

    /**
     * Whether this role may manage the organization's settings, seasons and members.
     */
    public function canManageOrganization(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Coach => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Coach => 'Coach',
        };
    }
}
