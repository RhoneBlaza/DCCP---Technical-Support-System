<?php

namespace App\Services;

use App\Enums\TicketStatusType;
use App\Models\Ticket;
use DateInterval;

class SlaCalculator
{
    /**
     * Whether the ticket is currently paused (status type "pending").
     */
    public function isPaused(Ticket $ticket): bool
    {
        return $ticket->status->type === TicketStatusType::Pending
            && $ticket->sla_paused_at !== null;
    }

    /**
     * Whether the ticket is overdue, per the definition in the spec:
     * due_at < now, not paused, status type not resolved/closed.
     */
    public function isOverdue(Ticket $ticket): bool
    {
        if ($this->isPaused($ticket) || $this->isFinished($ticket)) {
            return false;
        }

        return $ticket->due_at !== null && $ticket->due_at->isPast();
    }

    /**
     * The frozen SLA verdict for a finished ticket: "met", "breached", or null
     * while the ticket is still running.
     *
     * A finished ticket can never be "overdue" — the countdown has stopped. The
     * verdict is decided once, by comparing the moment work stopped against the
     * deadline, so it does not drift as time passes.
     */
    public function finishedOutcome(Ticket $ticket): ?string
    {
        if (! $this->isFinished($ticket) || $ticket->due_at === null) {
            return null;
        }

        $finishedAt = $ticket->closed_at ?? $ticket->resolved_at;

        if ($finishedAt === null) {
            return null;
        }

        return $finishedAt->greaterThan($ticket->due_at) ? 'breached' : 'met';
    }

    /**
     * Remaining time as a human label, e.g. "2h 15m remaining",
     * "OVERDUE: 1h 32m", "SLA paused", "SLA met" or "SLA breached".
     */
    public function displayLabel(Ticket $ticket): string
    {
        if ($this->isPaused($ticket)) {
            return 'SLA paused';
        }

        if ($ticket->due_at === null) {
            return 'No SLA';
        }

        $outcome = $this->finishedOutcome($ticket);

        if ($outcome !== null) {
            return $outcome === 'met' ? 'SLA met' : 'SLA breached';
        }

        if ($ticket->due_at->isPast()) {
            $diff = $ticket->due_at->diff(now());

            return 'OVERDUE: '.$this->humanDuration($diff, absolute: true);
        }

        $diff = now()->diff($ticket->due_at);

        return $this->humanDuration($diff).' remaining';
    }

    /**
     * Minutes remaining before the ticket goes overdue (0 when overdue, paused
     * or finished).
     */
    public function minutesRemaining(Ticket $ticket): int
    {
        if ($this->isPaused($ticket) || $this->isFinished($ticket) || $ticket->due_at === null) {
            return 0;
        }

        return max(0, (int) now()->diffInMinutes($ticket->due_at, false));
    }

    protected function isFinished(Ticket $ticket): bool
    {
        return in_array($ticket->status->type, [TicketStatusType::Resolved, TicketStatusType::Closed], true);
    }

    protected function humanDuration(DateInterval $diff, bool $absolute = false): string
    {
        $days = (int) $diff->d;
        $hours = (int) $diff->h;
        $minutes = (int) $diff->i;

        $parts = [];
        if ($days > 0) {
            $parts[] = $days.'d';
        }
        if ($hours > 0 || count($parts) > 0) {
            $parts[] = $hours.'h';
        }
        $parts[] = $minutes.'m';

        return implode(' ', $parts);
    }
}
