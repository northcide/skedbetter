<?php

namespace App\Notifications;

use App\Models\ScheduleEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleEntryCreated extends Notification
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
            ->subject("New Schedule: {$entry->team->name}")
            ->greeting("New schedule entry for {$entry->team->name}")
            ->line("**Date:** {$entry->date->format('l, M j, Y')}")
            ->line("**Time:** " . self::fmt($entry->start_time) . ' – ' . self::fmt($entry->end_time))
            ->line("**Field:** {$entry->field->name} at {$entry->field->location->name}")
            ->line("**Type:** " . ucfirst($entry->type->value ?? $entry->type));
    }

    public static function fmt(string $time): string
    {
        $parts = explode(':', $time);
        $h = (int) $parts[0];
        $m = $parts[1] ?? '00';
        $ampm = $h >= 12 ? 'PM' : 'AM';
        $h12 = $h === 0 ? 12 : ($h > 12 ? $h - 12 : $h);
        return "{$h12}:{$m} {$ampm}";
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'schedule_created',
            'entry_id' => $this->entry->id,
            'team_name' => $this->entry->team->name ?? '',
            'date' => $this->entry->date->toDateString(),
            'start_time' => $this->entry->start_time,
            'end_time' => $this->entry->end_time,
            'field_name' => $this->entry->field->name ?? '',
            'message' => "New schedule for {$this->entry->team->name} on {$this->entry->date->format('M j')}",
        ];
    }
}
