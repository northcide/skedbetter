<?php

namespace App\Services\Scheduling;

use App\Models\RecurrenceGroup;
use App\Models\ScheduleEntry;
use App\Services\Scheduling\DTO\ConflictResult;
use App\Services\Scheduling\DTO\ScheduleRequest;
use Illuminate\Support\Facades\DB;

class BulkScheduler
{
    public function __construct(
        protected ConflictDetector $conflictDetector,
        protected ConstraintValidator $constraintValidator,
        protected RecurrenceExpander $recurrenceExpander,
    ) {}

    /**
     * Create recurring schedule entries.
     *
     * @return array{created: int, skipped: array<array{date: string, reasons: array}>}
     */
    public function createRecurring(array $baseData, array $recurrencePattern, int $userId): array
    {
        $dates = $this->recurrenceExpander->expand($recurrencePattern, $baseData['date']);

        return DB::transaction(function () use ($baseData, $dates, $recurrencePattern, $userId) {
            // Create the recurrence group
            $group = RecurrenceGroup::create([
                'league_id' => $baseData['league_id'],
                'pattern' => $recurrencePattern,
                'created_by' => $userId,
            ]);

            $created = 0;
            $skipped = [];

            foreach ($dates as $date) {
                $data = array_merge($baseData, ['date' => $date]);
                $request = ScheduleRequest::fromArray($data);

                // Check conflicts
                $conflicts = $this->conflictDetector->check($request);
                if ($conflicts->hasConflicts()) {
                    $skipped[] = [
                        'date' => $date,
                        'reasons' => $conflicts->getAllMessages(),
                    ];
                    continue;
                }

                // Check constraints
                $constraints = $this->constraintValidator->validate($request);
                if ($constraints->hasConflicts()) {
                    $skipped[] = [
                        'date' => $date,
                        'reasons' => $constraints->getAllMessages(),
                    ];
                    continue;
                }

                ScheduleEntry::create(array_merge($data, [
                    'created_by' => $userId,
                    'recurrence_group_id' => $group->id,
                ]));

                $created++;
            }

            // If nothing was created, clean up the empty group
            if ($created === 0) {
                $group->delete();
            }

            return [
                'created' => $created,
                'skipped' => $skipped,
                'total' => count($dates),
                'recurrence_group_id' => $created > 0 ? $group->id : null,
            ];
        });
    }

    /**
     * Delete all future entries in a recurrence group from a given date.
     */
    public function deleteFutureInGroup(int $recurrenceGroupId, string $fromDate, int $userId): int
    {
        return ScheduleEntry::where('recurrence_group_id', $recurrenceGroupId)
            ->where('date', '>=', $fromDate)
            ->where('status', '!=', 'cancelled')
            ->update([
                'status' => 'cancelled',
                'updated_by' => $userId,
            ]);
    }

    /**
     * Detach an entry from its recurrence group (make it standalone).
     */
    public function detachFromGroup(ScheduleEntry $entry): void
    {
        $entry->update(['recurrence_group_id' => null]);
    }
}
