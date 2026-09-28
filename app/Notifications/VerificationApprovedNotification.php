<?php

namespace App\Notifications;

use App\Models\VerificationRequest;
use App\Support\MailConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the requester their registration was approved.
 */
class VerificationApprovedNotification extends Notification
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
        return (new MailMessage)
            ->subject('Your account has been approved')
            ->greeting('Hello '.$notifiable->first_name.',')
            ->line('Your account registration has been approved by the administrator.')
            ->line('You can now sign in to submit and track IT support requests.')
            ->action('Sign in', route('login'))
            ->line('Thank you for using '.settings('system_name', 'the helpdesk').'.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Account approved',
            'text' => 'Your account has been approved. You can now sign in to the system.',
            'url' => route('login'),
        ];
    }
}
