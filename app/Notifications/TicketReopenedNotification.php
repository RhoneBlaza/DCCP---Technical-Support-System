<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class TicketReopenedNotification extends AbstractTicketNotification
{
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'text' => 'This ticket has been reopened.',
            'url' => route('tickets.show', $this->ticket->ticket_number),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->ticketMail(
            (new MailMessage)
                ->greeting('Hello '.$notifiable->full_name.'!')
                ->line('This ticket has been reopened and is waiting for attention.')
        );
    }
}
