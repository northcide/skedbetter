<?php

namespace App\Notifications;

use App\Models\ScheduleEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleEntryCancelled extends Notification
{
    use Queueable;

    public function __construct(protected ScheduleEntry $entry) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $entry = $this->entry->load(['team', 'field.location']);

        return (new MailMessage)
            ->subject("Schedule Cancelled: {$entry->team->name}")
            ->greeting("A schedule entry has been cancelled")
            ->line("**Team:** {$entry->team->name}")
            ->line("**Date:** {$entry->date->format('l, M j, Y')}")
            ->line("**Time:** {$entry->start_time} - {$entry->end_time}")
            ->line("**Field:** {$entry->field->name} at {$entry->field->location->name}")
            ->line('This time slot is now available.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'schedule_cancelled',
            'entry_id' => $this->entry->id,
            'team_name' => $this->entry->team->name ?? '',
            'date' => $this->entry->date->toDateString(),
            'message' => "Schedule cancelled for {$this->entry->team->name} on {$this->entry->date->format('M j')}",
        ];
    }
}
