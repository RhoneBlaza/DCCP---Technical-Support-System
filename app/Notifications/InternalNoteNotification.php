<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Notifications\Messages\MailMessage;

class InternalNoteNotification extends AbstractTicketNotification
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
            'text' => 'An internal note was added to this ticket.',
            'url' => route('tickets.show', $this->ticket->ticket_number),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->ticketMail(
            (new MailMessage)
                ->greeting('Hello '.$notifiable->full_name.'!')
                ->line('An internal note was added to this ticket.')
        );

        // Internal note content is never included in mail notices.
        return $mail;
    }
}
