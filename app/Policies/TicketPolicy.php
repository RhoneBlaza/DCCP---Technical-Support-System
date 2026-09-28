<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Services\SettingsService;

class TicketPolicy
{
    /**
     * Deny with a 404 whenever a requester tries to open another user's ticket,
     * to prevent ticket enumeration.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ($ticket->requester_id !== $user->id) {
            abort(404, 'Ticket not found.');
        }

        return true;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function queue(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff mastery over a ticket's workflow (status, priority, category,
     * resolve, close). Requesters only ever close their own tickets.
     */
    public function manage(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return ! $ticket->isClosed;
        }

        return $ticket->requester_id === $user->id;
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && ! $ticket->isClosed;
    }

    public function unassign(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && $ticket->assigned_to !== null && ! $ticket->isClosed;
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && ! $ticket->isClosed;
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && ! $ticket->isClosed;
    }

    public function changeCategory(User $user, Ticket $ticket): bool
    {
        return $user->isStaff() && ! $ticket->isClosed;
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $ticket->requester_id === $user->id && ! $ticket->isClosed;
    }

    public function internalNote(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }

    public function resolve(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return ! $ticket->isClosed && ! $ticket->isResolved;
        }

        return false;
    }

    public function close(User $user, Ticket $ticket): bool
    {
        if ($ticket->isClosed) {
            return false;
        }

        if ($user->isStaff()) {
            return true;
        }

        return $ticket->requester_id === $user->id;
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        if ($ticket->requester_id !== $user->id) {
            return false;
        }

        if (! $ticket->isResolved && ! $ticket->isClosed) {
            return false;
        }

        if (! $ticket->isClosed) {
            return true;
        }

        $windowDays = (new SettingsService)->int('reopen_window_days', 7);

        return $ticket->closed_at !== null
            && $ticket->closed_at->copy()->addDays($windowDays)->isFuture();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function downloadAttachment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
