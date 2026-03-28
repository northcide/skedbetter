<?php

namespace App\Notifications;

use App\Models\ScheduleEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScheduleEntryUpdated extends Notification
{
    use Queueable;

    public function __construct(
        protected ScheduleEntry $entry,
        protected array $changes = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $entry = $this->entry->load(['team', 'field.location']);
        $mail = (new MailMessage)
            ->subject("Schedule Updated: {$entry->team->name}")
            ->greeting("Schedule update for {$entry->team->name}");

        if (! empty($this->changes)) {
            foreach ($this->changes as $field => $change) {
                $mail->line("**{$field}:** {$change['from']} → {$change['to']}");
            }
        }

        $mail->line("**Current:** {$entry->date->format('l, M j, Y')} {$entry->start_time} - {$entry->end_time}")
            ->line("**Field:** {$entry->field->name} at {$entry->field->location->name}");

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'schedule_updated',
            'entry_id' => $this->entry->id,
            'team_name' => $this->entry->team->name ?? '',
            'changes' => $this->changes,
            'message' => "Schedule updated for {$this->entry->team->name} on {$this->entry->date->format('M j')}",
        ];
    }
}
