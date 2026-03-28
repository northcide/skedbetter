<?php

namespace App\Notifications;

use App\Models\LeagueInvitation as InvitationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeagueInvitation extends Notification
{
    use Queueable;

    public function __construct(protected InvitationModel $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invitation = $this->invitation->load(['league', 'inviter']);
        $roleName = str_replace('_', ' ', ucwords($invitation->role, '_'));

        $url = url("/invitations/{$invitation->token}");

        return (new MailMessage)
            ->subject("You're invited to {$invitation->league->name}")
            ->greeting("You've been invited!")
            ->line("{$invitation->inviter->name} has invited you to join **{$invitation->league->name}** as a **{$roleName}**.")
            ->action('Accept Invitation', $url)
            ->line('This invitation expires in 7 days.');
    }
}
