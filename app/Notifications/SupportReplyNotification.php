<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Notifications\Messages\MailMessage;

class SupportReplyNotification extends AbstractTicketNotification
{
    public function __construct(
        Ticket $ticket,
        public TicketMessage $message,
    ) {
        parent::__construct($ticket);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'text' => 'Support has replied to your ticket.',
            'url' => route('tickets.show', $this->ticket->ticket_number),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->ticketMail(
            (new MailMessage)
                ->greeting('Hello '.$notifiable->full_name.'!')
                ->line('A support technician has replied to your ticket:')
                ->line('"'.mb_strimwidth($this->message->body, 0, 300).'"')
        );
    }
}
