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
                $label = $this->humanLabel($field);
                $from = $this->humanValue($field, $change['from']);
                $to = $this->humanValue($field, $change['to']);
                $mail->line("**{$label}:** {$from} → {$to}");
            }
        }

        $mail->line("**Current:** {$entry->date->format('l, M j, Y')} " . ScheduleEntryCreated::fmt($entry->start_time) . ' – ' . ScheduleEntryCreated::fmt($entry->end_time))
            ->line("**Field:** {$entry->field->name} at {$entry->field->location->name}");

        return $mail;
    }

    protected function humanLabel(string $field): string
    {
        return match ($field) {
            'date' => 'Date',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'field_id' => 'Field',
            'team_id' => 'Team',
            'status' => 'Status',
            'type' => 'Type',
            default => str_replace('_', ' ', ucfirst($field)),
        };
    }

    protected function humanValue(string $field, mixed $value): string
    {
        if ($value === null) return '—';

        if (in_array($field, ['start_time', 'end_time'])) {
            return ScheduleEntryCreated::fmt((string) $value);
        }

        if ($field === 'date' && $value) {
            try { return \Carbon\Carbon::parse($value)->format('M j, Y'); } catch (\Exception $e) {}
        }

        if ($field === 'field_id') {
            $f = \App\Models\Field::withoutGlobalScopes()->with('location')->find($value);
            return $f ? $f->name . ' @ ' . ($f->location->name ?? '') : (string) $value;
        }

        if ($field === 'team_id') {
            return \App\Models\Team::withoutGlobalScopes()->find($value)?->name ?? (string) $value;
        }

        if ($field === 'type') {
            return ucfirst((string) $value);
        }

        if ($field === 'status') {
            return ucfirst((string) $value);
        }

        return (string) $value;
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
