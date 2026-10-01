<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

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

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return ! $ticket->isClosed;
        }

        if ($ticket->requester_id !== $user->id) {
            abort(404, 'Ticket not found.');
        }

        return ! $ticket->isClosed;
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

    /**
     * Reopening is only meaningful while the ticket sits in Resolved, and only
     * the owner or staff may do it. Both the UI button and the workflow assert
     * the same rule, so the button can never advertise an action the server
     * will reject.
     */
    public function reopen(User $user, Ticket $ticket): bool
    {
        if (! $ticket->isResolved) {
            return false;
        }

        return $user->isStaff() || $ticket->requester_id === $user->id;
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
