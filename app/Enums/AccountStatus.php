<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending approval',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
        };
    }

    /**
     * Whether the account may sign in.
     */
    public function canSignIn(): bool
    {
        return $this === self::Approved;
    }

    /**
     * Valid values for validation rules.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
