<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected const CACHE_KEY = 'tsts.settings';

    /**
     * Get a setting value, cast to its stored type.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (! array_key_exists($key, $all)) {
            $defaults = config('tsts.default_settings');

            return isset($defaults[$key]) ? $this->cast($defaults[$key]['value'], $defaults[$key]['type']) : $default;
        }

        $row = $all[$key];

        return $this->cast($row['value'], $row['type']);
    }

    /**
     * Get a numeric setting.
     */
    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    /**
     * Get a boolean setting.
     */
    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        return (bool) $value;
    }

    /**
     * Get a JSON-decoded (array) setting.
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }

    /**
     * Set a single setting and refresh the cache.
     */
    public function set(string $key, mixed $value, string $type = 'string'): Setting
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $this->serialize($value), 'type' => $type]
        );

        $this->flush();

        return $setting;
    }

    /**
     * All settings as a keyed array of [value, type], cached.
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Setting::all(['key', 'value', 'type'])
                ->keyBy('key')
                ->map(fn ($row) => ['value' => $row->value, 'type' => $row->type])
                ->toArray();
        });
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            default => (string) $value,
        };
    }

    protected function serialize(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
