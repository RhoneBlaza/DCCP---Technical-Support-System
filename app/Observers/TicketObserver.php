<?php

namespace App\Observers;

use App\Enums\TicketStatusType;
use App\Models\Ticket;
use App\Models\TicketStatus;

/**
 * Single owner of a ticket's status timestamps.
 *
 * Every status change in the application funnels through here, so
 * status_updated_at / resolved_at / reopened_at can never drift out of sync
 * with the status they describe, no matter which controller or command moved
 * the ticket.
 */
class TicketObserver
{
    public function creating(Ticket $ticket): void
    {
        if ($ticket->status_updated_at === null) {
            $ticket->status_updated_at = now();
        }
    }

    public function updating(Ticket $ticket): void
    {
        if (! $ticket->isDirty('status_id')) {
            return;
        }

        $now = now();

        $ticket->status_updated_at = $now;

        $status = TicketStatus::find($ticket->status_id);

        if ($status?->type === TicketStatusType::Resolved) {
            // Keep the original resolution stamp if a later transition re-resolves.
            $ticket->resolved_at ??= $now;
        }

        if ($status?->key === 'reopened') {
            $ticket->reopened_at = $now;
        }
    }
}
