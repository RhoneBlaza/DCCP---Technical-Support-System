<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Support = 'support';
    case Requester = 'requester';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Support => 'Technical Support',
            self::Requester => 'Requester',
        };
    }

    /**
     * Whether the role belongs to technical staff (support or admin).
     */
    public function isStaff(): bool
    {
        return $this === self::Admin || $this === self::Support;
    }

    /**
     * Whether the role can access the admin section.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Valid values for validation rules.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
