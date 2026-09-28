<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class TicketResolvedNotification extends AbstractTicketNotification
{
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'text' => 'Your ticket has been marked as resolved.',
            'url' => route('tickets.show', $this->ticket->ticket_number),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->ticketMail(
            (new MailMessage)
                ->greeting('Hello '.$notifiable->full_name.'!')
                ->line('Your ticket has been marked as resolved.')
                ->line('If your issue is not yet fully addressed, you may reopen the ticket, and it will be brought back to our attention.')
        );
    }
}
