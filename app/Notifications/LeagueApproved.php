<?php

namespace App\Notifications;

use App\Models\League;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeagueApproved extends Notification
{
    use Queueable;

    public function __construct(public League $league) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your League \"{$this->league->name}\" Has Been Approved")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your league \"{$this->league->name}\" has been approved and is ready to use!")
            ->action('Get Started', url("/leagues/{$this->league->slug}"))
            ->line('You can now set up seasons, divisions, teams, and start scheduling.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Your league \"{$this->league->name}\" has been approved!",
        ];
    }
}
