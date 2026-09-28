<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class TicketAssignedNotification extends AbstractTicketNotification
{
    public function __construct(
        public Ticket $ticket,
        public ?User $assigner = null,
        public ?User $assignee = null,
    ) {
        parent::__construct($ticket);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'text' => $this->assignee?->id === $notifiable->id
                ? 'This ticket has been assigned to you.'
                : 'The assignment of this ticket has been updated.',
            'url' => route('tickets.show', $this->ticket->ticket_number),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->assignee?->full_name ?? 'a support technician';

        return $this->ticketMail(
            (new MailMessage)
                ->greeting('Hello '.$notifiable->full_name.'!')
                ->line('This ticket has been assigned to '.$name.'.')
        );
    }
}
