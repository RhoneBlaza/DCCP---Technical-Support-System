<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\Notifications\Messages\MailMessage;

class TicketStatusChangedNotification extends AbstractTicketNotification
{
    public function __construct(
        Ticket $ticket,
        public ?TicketStatus $from,
        public TicketStatus $to,
    ) {
        parent::__construct($ticket);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'text' => sprintf('Status changed from %s to %s.',
                $this->from?->name ?? '-', $this->to->name),
            'url' => route('tickets.show', $this->ticket->ticket_number),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->ticketMail(
            (new MailMessage)
                ->greeting('Hello '.$notifiable->full_name.'!')
                ->line(sprintf('The status of this ticket changed from %s to %s.',
                    $this->from?->name ?? '-', $this->to->name))
        );
    }
}
