<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\ScheduleEntry;
use App\Notifications\ScheduleEntryCancelled;
use App\Notifications\ScheduleEntryCreated;
use App\Notifications\ScheduleEntryUpdated;

class ScheduleEntryObserver
{
    public function created(ScheduleEntry $entry): void
    {
        $this->auditLog($entry, 'created');
        $this->notifyTeamMembers($entry, 'created');
    }

    public function updated(ScheduleEntry $entry): void
    {
        $changes = $this->getChanges($entry);

        if (empty($changes)) {
            return;
        }

        // If status changed to cancelled
        if (isset($changes['status']) && $changes['status']['to'] === 'cancelled') {
            $this->auditLog($entry, 'cancelled');
            $this->notifyTeamMembers($entry, 'cancelled');
            return;
        }

        $this->auditLog($entry, 'updated', $changes);
        $this->notifyTeamMembers($entry, 'updated', $changes);
    }

    public function deleted(ScheduleEntry $entry): void
    {
        $this->auditLog($entry, 'deleted');
    }

    protected function auditLog(ScheduleEntry $entry, string $action, array $changes = []): void
    {
        AuditLog::create([
            'league_id' => $entry->league_id,
            'user_id' => $entry->updated_by ?? $entry->created_by,
            'action' => $action,
            'auditable_type' => ScheduleEntry::class,
            'auditable_id' => $entry->id,
            'old_values' => ! empty($changes) ? array_map(fn($c) => $c['from'] ?? null, $changes) : null,
            'new_values' => ! empty($changes) ? array_map(fn($c) => $c['to'] ?? null, $changes) : $entry->toArray(),
            'ip_address' => request()?->ip(),
        ]);
    }

    protected function notifyTeamMembers(ScheduleEntry $entry, string $type, array $changes = []): void
    {
        $team = $entry->team;
        if (! $team) {
            return;
        }

        $users = $team->users;
        if ($users->isEmpty()) {
            return;
        }

        $entry->loadMissing(['team', 'field.location']);

        $notification = match ($type) {
            'created' => new ScheduleEntryCreated($entry),
            'updated' => new ScheduleEntryUpdated($entry, $changes),
            'cancelled' => new ScheduleEntryCancelled($entry),
            default => null,
        };

        if ($notification) {
            foreach ($users as $user) {
                $user->notify($notification);
            }
        }
    }

    protected function getChanges(ScheduleEntry $entry): array
    {
        $tracked = ['date', 'start_time', 'end_time', 'field_id', 'team_id', 'status', 'type'];
        $changes = [];

        foreach ($tracked as $field) {
            if ($entry->wasChanged($field)) {
                $changes[$field] = [
                    'from' => $entry->getOriginal($field),
                    'to' => $entry->getAttribute($field),
                ];
            }
        }

        return $changes;
    }
}
