<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class NewTicketSupportNotification extends AbstractTicketNotification
{
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'text' => 'A new technical support request requires attention.',
            'url' => route('tickets.show', $this->ticket->ticket_number),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->ticketMail(
            (new MailMessage)
                ->greeting('Hello '.$notifiable->full_name.'!')
                ->line('A new technical support request has been submitted and is waiting in the support queue.')
        );
    }
}
