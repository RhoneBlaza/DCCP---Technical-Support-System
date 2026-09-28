<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use App\Support\MailConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Asks the requester to resubmit their ID because more information is needed.
 */
class VerificationResubmissionRequestedNotification extends Notification
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
            ->subject('We need more information to verify your ID')
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('The administrator needs more information before your registration can be approved.');

        if ($this->verificationRequest->decision_note) {
            $message->line('What to do next: '.$this->verificationRequest->decision_note);
        }

        $message->action('Resubmit your ID', route('register'))
            ->line('Please resubmit your registration with a clearly readable ID document.');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Verification needs more information',
            'text' => 'The administrator requested a resubmission of your ID. Please submit a new registration with a readable ID document.',
            'url' => route('account.status', $this->verificationRequest->user),
        ];
    }
}
