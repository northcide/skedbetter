<?php

namespace App\Services\Scheduling;

use App\Services\Scheduling\DTO\ConflictResult;
use App\Services\Scheduling\DTO\ScheduleRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConstraintValidator
{
    public function validate(ScheduleRequest $request): ConflictResult
    {
        $result = new ConflictResult();
        $constraints = $this->loadConstraints($request);

        foreach ($constraints as $constraint) {
            $value = json_decode($constraint->value, true);

            match ($constraint->constraint_type) {
                'time_block_length' => $this->validateTimeBlockLength($request, $value, $result),
                'earliest_start_time' => $this->validateEarliestStart($request, $value, $result),
                'latest_end_time' => $this->validateLatestEnd($request, $value, $result),
                'allowed_days_of_week' => $this->validateAllowedDays($request, $value, $result),
                'min_gap_between_slots' => $this->validateMinGap($request, $value, $result),
                'max_daily_slots' => $this->validateMaxDaily($request, $value, $result),
                default => null,
            };
        }

        return $result;
    }

    protected function loadConstraints(ScheduleRequest $request): array
    {
        // Load all active constraints for this league/season, ordered by specificity
        // Most specific (team) wins over less specific (league)
        return DB::table('scheduling_constraints')
            ->where('league_id', $request->leagueId)
            ->where('season_id', $request->seasonId)
            ->where('is_active', true)
            ->orderByRaw("CASE scope_type WHEN 'team' THEN 1 WHEN 'division' THEN 2 WHEN 'league' THEN 3 ELSE 4 END")
            ->get()
            ->unique('constraint_type') // Keep most specific per type
            ->all();
    }

    protected function validateTimeBlockLength(ScheduleRequest $request, array $value, ConflictResult $result): void
    {
        $allowedMinutes = $value['minutes'] ?? null;
        if (! $allowedMinutes) {
            return;
        }

        $start = Carbon::parse($request->date . ' ' . $request->startTime);
        $end = Carbon::parse($request->date . ' ' . $request->endTime);
        $durationMinutes = $start->diffInMinutes($end);

        $allowed = is_array($allowedMinutes) ? $allowedMinutes : [$allowedMinutes];

        if (! in_array($durationMinutes, $allowed)) {
            $allowedStr = implode(', ', $allowed);
            $result->addViolation(
                'constraint',
                "Time block length ({$durationMinutes} min) is not allowed. Allowed lengths: {$allowedStr} minutes."
            );
        }
    }

    protected function validateEarliestStart(ScheduleRequest $request, array $value, ConflictResult $result): void
    {
        $earliest = $value['time'] ?? null;
        if (! $earliest) {
            return;
        }

        if ($request->startTime < $earliest) {
            $result->addViolation(
                'constraint',
                "Start time ({$request->startTime}) is before the earliest allowed time ({$earliest})."
            );
        }
    }

    protected function validateLatestEnd(ScheduleRequest $request, array $value, ConflictResult $result): void
    {
        $latest = $value['time'] ?? null;
        if (! $latest) {
            return;
        }

        if ($request->endTime > $latest) {
            $result->addViolation(
                'constraint',
                "End time ({$request->endTime}) is after the latest allowed time ({$latest})."
            );
        }
    }

    protected function validateAllowedDays(ScheduleRequest $request, array $value, ConflictResult $result): void
    {
        $days = $value['days'] ?? null;
        if (! $days) {
            return;
        }

        $dayOfWeek = Carbon::parse($request->date)->dayOfWeek;
        if (! in_array($dayOfWeek, $days)) {
            $dayName = Carbon::parse($request->date)->format('l');
            $result->addViolation(
                'constraint',
                "Scheduling is not allowed on {$dayName}."
            );
        }
    }

    protected function validateMinGap(ScheduleRequest $request, array $value, ConflictResult $result): void
    {
        $minGapMinutes = $value['minutes'] ?? null;
        if (! $minGapMinutes) {
            return;
        }

        // Check if the team has another entry on the same day that's too close
        $query = DB::table('schedule_entries')
            ->where('team_id', $request->teamId)
            ->where('date', $request->date)
            ->where('status', '!=', 'cancelled');

        if ($request->excludeEntryId) {
            $query->where('id', '!=', $request->excludeEntryId);
        }

        $entries = $query->get();

        $proposedStart = Carbon::parse($request->date . ' ' . $request->startTime);
        $proposedEnd = Carbon::parse($request->date . ' ' . $request->endTime);

        foreach ($entries as $entry) {
            $existingStart = Carbon::parse($request->date . ' ' . $entry->start_time);
            $existingEnd = Carbon::parse($request->date . ' ' . $entry->end_time);

            // Gap between proposed end and existing start
            if ($proposedEnd->lte($existingStart)) {
                $gap = $proposedEnd->diffInMinutes($existingStart);
                if ($gap < $minGapMinutes) {
                    $result->addViolation(
                        'constraint',
                        "Minimum gap of {$minGapMinutes} minutes required between slots. Only {$gap} minutes gap before existing slot at {$entry->start_time}."
                    );
                }
            }

            // Gap between existing end and proposed start
            if ($existingEnd->lte($proposedStart)) {
                $gap = $existingEnd->diffInMinutes($proposedStart);
                if ($gap < $minGapMinutes) {
                    $result->addViolation(
                        'constraint',
                        "Minimum gap of {$minGapMinutes} minutes required between slots. Only {$gap} minutes gap after existing slot ending at {$entry->end_time}."
                    );
                }
            }
        }
    }

    protected function validateMaxDaily(ScheduleRequest $request, array $value, ConflictResult $result): void
    {
        $maxDaily = $value['max'] ?? null;
        if (! $maxDaily) {
            return;
        }

        $query = DB::table('schedule_entries')
            ->where('team_id', $request->teamId)
            ->where('date', $request->date)
            ->where('status', '!=', 'cancelled');

        if ($request->excludeEntryId) {
            $query->where('id', '!=', $request->excludeEntryId);
        }

        $count = $query->count();

        if ($count >= $maxDaily) {
            $result->addViolation(
                'constraint',
                "Team has reached the daily limit of {$maxDaily} slots (currently {$count} on this date)."
            );
        }
    }
}
