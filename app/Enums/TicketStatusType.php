<?php

namespace App\Enums;

enum TicketStatusType: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Closed = 'closed';

    /**
     * Upper bound for a status sort order. Sort order is a display rank, not a
     * weight, so it stays far below the old 0-100 range that read as a
     * percentage.
     */
    public const MAX_SORT_ORDER = 100;

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
