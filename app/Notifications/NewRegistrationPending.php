<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Informs active administrators that a new user account is awaiting approval.
 */
class NewRegistrationPending extends Notification
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New registration awaiting approval',
            'text' => $this->user->full_name.' ('.$this->user->email.') registered an account and is awaiting your approval.',
            'url' => route('admin.users.index', ['status' => 'pending']),
        ];
    }
}
