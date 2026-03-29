<?php

namespace App\Services\Scheduling;

use App\Models\ScheduleEntry;
use App\Services\Scheduling\DTO\ConflictResult;
use App\Services\Scheduling\DTO\ScheduleRequest;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    public function __construct(
        protected ConflictDetector $conflictDetector,
        protected ConstraintValidator $constraintValidator,
    ) {}

    public function getConflictDetector(): ConflictDetector
    {
        return $this->conflictDetector;
    }

    public function validate(ScheduleRequest $request): ConflictResult
    {
        $conflicts = $this->conflictDetector->check($request);

        if ($conflicts->hasConflicts()) {
            return $conflicts;
        }

        $constraints = $this->constraintValidator->validate($request);

        // Carry forward any warnings from conflict check
        foreach ($conflicts->getAllWarnings() as $warning) {
            $constraints->addWarning('booking_window', $warning);
        }

        return $constraints;
    }

    public function create(array $data, int $userId): ScheduleEntry|ConflictResult
    {
        $request = ScheduleRequest::fromArray($data);
        $result = $this->validate($request);

        if ($result->hasConflicts()) {
            return $result;
        }

        return DB::transaction(function () use ($data, $userId, $request) {
            // Lock the field's schedule entries for the date to prevent race conditions
            ScheduleEntry::where('field_id', $request->fieldId)
                ->where('date', $request->date)
                ->lockForUpdate()
                ->get();

            // Double-check after acquiring lock
            $recheck = $this->conflictDetector->check($request);
            if ($recheck->hasConflicts()) {
                return $recheck;
            }

            return ScheduleEntry::create(array_merge($data, [
                'created_by' => $userId,
            ]));
        });
    }

    public function update(ScheduleEntry $entry, array $data, int $userId): ScheduleEntry|ConflictResult
    {
        $merged = array_merge([
            'league_id' => $entry->league_id,
            'season_id' => $entry->season_id,
            'field_id' => $entry->field_id,
            'team_id' => $entry->team_id,
            'date' => $entry->date->toDateString(),
            'start_time' => $entry->start_time,
            'end_time' => $entry->end_time,
        ], $data, [
            'exclude_entry_id' => $entry->id,
        ]);

        $request = ScheduleRequest::fromArray($merged);
        $result = $this->validate($request);

        if ($result->hasConflicts()) {
            return $result;
        }

        return DB::transaction(function () use ($entry, $data, $userId, $request) {
            ScheduleEntry::where('field_id', $request->fieldId)
                ->where('date', $request->date)
                ->where('id', '!=', $entry->id)
                ->lockForUpdate()
                ->get();

            $recheck = $this->conflictDetector->check($request);
            if ($recheck->hasConflicts()) {
                return $recheck;
            }

            $entry->update(array_merge($data, [
                'updated_by' => $userId,
            ]));

            return $entry->fresh();
        });
    }

    public function cancel(ScheduleEntry $entry, int $userId): ScheduleEntry
    {
        $entry->update([
            'status' => 'cancelled',
            'updated_by' => $userId,
        ]);

        return $entry->fresh();
    }
}
