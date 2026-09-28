<?php

namespace App\Support;

class MailConfig
{
    /**
     * Whether mail is considered "configured" for notification delivery.
     * Working without mail is fully supported (database channel only).
     */
    public static function isConfigured(): bool
    {
        $default = (string) config('mail.default');

        $host = (string) config("mail.mailers.{$default}.host");

        return $default === 'log'
            || $default === 'array'
            || $host !== '';
    }
}
