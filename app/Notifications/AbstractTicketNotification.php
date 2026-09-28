<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Services\SettingsService;
use App\Support\MailConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

abstract class AbstractTicketNotification extends Notification
{
    use Queueable;

    /**
     * Notifications are dispatched from inside DB transactions by the
     * workflow service; they must only be persisted after the commit.
     */
    public function __construct(public Ticket $ticket)
    {
        $this->afterCommit = true;
    }

    public function via(object $notifiable): array
    {
        return MailConfig::isConfigured()
            ? ['database', 'mail']
            : ['database'];
    }

    abstract public function toArray(object $notifiable): array;

    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * A failing mail channel must never break the request; log and continue.
     */
    public function failed($notifiable, $e, $channel = null): void
    {
        Log::warning('Notification channel failed for '.static::class, [
            'error' => $e->getMessage(),
            'notifiable' => (string) $notifiable->routes['database'] ?? $notifiable->id,
        ]);
    }

    /**
     * Build the shared, branded mail layout with ticket metadata and a
     * "View Ticket" button. Subclasses prepend their intro lines first.
     */
    protected function ticketMail(MailMessage $mail): MailMessage
    {
        $ticket = $this->ticket;
        $systemName = (string) (new SettingsService)->get('system_name');

        return $mail
            ->subject('['.$ticket->ticket_number.'] '.$ticket->subject)
            ->line('**Ticket Number:** '.$ticket->ticket_number)
            ->line('**Subject:** '.$ticket->subject)
            ->line('**Requester:** '.($ticket->requester->full_name ?? '-'))
            ->line('**Department:** '.($ticket->department->name ?? '-'))
            ->line('**Priority:** '.$ticket->priority->name)
            ->line('**Status:** '.$ticket->status->name)
            ->action('View Ticket', route('tickets.show', $ticket->ticket_number))
            ->line('This is an automated message from the '.($systemName ?: config('app.name')).' system.');
    }
}
