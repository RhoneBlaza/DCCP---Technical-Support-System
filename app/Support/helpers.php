<?php

use App\Services\SettingsService;

if (! function_exists('settings')) {
    /**
     * Read a setting value (cached, typed).
     */
    function settings(string $key, mixed $default = null): mixed
    {
        try {
            return app(SettingsService::class)->get($key, $default);
        } catch (Throwable) {
            return $default;
        }
    }
}

if (! function_exists('settings_int')) {
    function settings_int(string $key, int $default = 0): int
    {
        try {
            return app(SettingsService::class)->int($key, $default);
        } catch (Throwable) {
            return $default;
        }
    }
}

if (! function_exists('format_file_size')) {
    /**
     * Human-friendly file size (bytes).
     */
    function format_file_size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        $index = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return round($size, $index === 0 ? 0 : 1).' '.$units[$index];
    }
}
