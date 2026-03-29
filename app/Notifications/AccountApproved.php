<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApproved extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your SkedBetter Account Has Been Approved')
            ->greeting("Hi {$notifiable->name},")
            ->line('Your SkedBetter account has been approved! You can now sign in and start using the platform.')
            ->action('Sign In', url('/login'))
            ->line('Welcome to SkedBetter!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Your account has been approved. Welcome to SkedBetter!',
        ];
    }
}
