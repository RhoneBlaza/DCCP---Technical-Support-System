<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use App\Support\MailConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the requester their registration was rejected.
 */
class VerificationRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public VerificationRequest $verificationRequest) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (MailConfig::isConfigured()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your registration was not approved')
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('Unfortunately, your registration could not be approved.');

        if ($this->verificationRequest->decision_note) {
            $message->line('Reason: '.$this->verificationRequest->decision_note);
        }

        $message
            ->action('Submit a new registration', route('register'))
            ->line('If you believe this is a mistake, please contact the IT Helpdesk.');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Registration not approved',
            'text' => 'Your registration was not approved. Please review the decision and reach out to the IT Helpdesk if needed.',
            'url' => route('account.status', $this->verificationRequest->user),
        ];
    }
}
