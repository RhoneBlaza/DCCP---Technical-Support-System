<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class NewTicketRequesterNotification extends AbstractTicketNotification
{
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'text' => 'Your technical support request has been submitted successfully.',
            'url' => route('tickets.show', $this->ticket->ticket_number),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->ticketMail(
            (new MailMessage)
                ->greeting('Hello '.$notifiable->full_name.'!')
                ->line('Your technical support request has been received and a support technician will attend to it soon.')
        );
    }
}
