<?php

namespace App\Enums;

enum MessageType: string
{
    case Public = 'public';
    case Internal = 'internal';

    public function label(): string
    {
        return $this === self::Internal ? 'Internal note' : 'Public reply';
    }
}
