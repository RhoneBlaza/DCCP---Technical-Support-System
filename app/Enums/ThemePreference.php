<?php

namespace App\Enums;

enum ThemePreference: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Light => 'Light',
            self::Dark => 'Dark',
            self::System => 'System',
        };
    }

    /**
     * Whether this preference should render dark regardless of the OS setting.
     */
    public function resolvesToDark(bool $systemPrefersDark): bool
    {
        return match ($this) {
            self::Light => false,
            self::Dark => true,
            self::System => $systemPrefersDark,
        };
    }

    /**
     * Valid values for validation rules.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
