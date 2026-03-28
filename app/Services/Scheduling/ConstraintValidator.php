<?php

namespace App\Services\Scheduling;

use App\Services\Scheduling\DTO\ConflictResult;
use App\Services\Scheduling\DTO\ScheduleRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConstraintValidator
{
    protected function fmt(string $time): string
    {
        $parts = explode(':', $time);
        $h = (int)$parts[0];
        $m = $parts[1] ?? '00';
        $ampm = $h >= 12 ? 'PM' : 'AM';
        $h12 = $h === 0 ? 12 : ($h > 12 ? $h - 12 : $h);
        return "{$h12}:{$m} {$ampm}";
    }

    public function validate(ScheduleRequest $request): ConflictResult
    {
        $result = new ConflictResult();

        // Field availability rules
        $this->validateFieldAvailability($request, $result);

        // Division max event duration
        $this->validateDivisionMaxDuration($request, $result);

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
                "Start time ({$this->fmt($request->startTime)}) is before the earliest allowed time ({$this->fmt($earliest)})."
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
                "End time ({$this->fmt($request->endTime)}) is after the latest allowed time ({$this->fmt($latest)})."
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
                        "Minimum gap of {$minGapMinutes} minutes required between slots. Only {$gap} minutes gap before existing slot at {$this->fmt($entry->start_time)}."
                    );
                }
            }

            // Gap between existing end and proposed start
            if ($existingEnd->lte($proposedStart)) {
                $gap = $existingEnd->diffInMinutes($proposedStart);
                if ($gap < $minGapMinutes) {
                    $result->addViolation(
                        'constraint',
                        "Minimum gap of {$minGapMinutes} minutes required between slots. Only {$gap} minutes gap after existing slot ending at {$this->fmt($entry->end_time)}."
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

    protected function validateDivisionMaxDuration(ScheduleRequest $request, ConflictResult $result): void
    {
        $team = DB::table('teams')->where('id', $request->teamId)->first();
        if (! $team) return;

        $division = DB::table('divisions')->where('id', $team->division_id)->first();
        if (! $division || ! $division->max_event_minutes) return;

        $start = Carbon::parse($request->date . ' ' . $request->startTime);
        $end = Carbon::parse($request->date . ' ' . $request->endTime);
        $duration = $start->diffInMinutes($end);

        if ($duration > $division->max_event_minutes) {
            $hours = floor($division->max_event_minutes / 60);
            $mins = $division->max_event_minutes % 60;
            $limit = $hours > 0 ? "{$hours}h" . ($mins > 0 ? " {$mins}m" : '') : "{$mins}m";
            $result->addViolation(
                'constraint',
                "Event duration ({$duration} min) exceeds the {$division->name} division maximum of {$limit}."
            );
        }
    }

    protected function validateFieldAvailability(ScheduleRequest $request, ConflictResult $result): void
    {
        $field = DB::table('fields')->where('id', $request->fieldId)->first();
        if (! $field) return;

        $date = Carbon::parse($request->date);
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Available days
        $availableDays = $field->available_days ? json_decode($field->available_days, true) : null;
        if ($availableDays && ! in_array($date->dayOfWeek, $availableDays)) {
            $allowed = array_map(fn($d) => $dayNames[$d] ?? $d, $availableDays);
            $result->addViolation(
                'field_availability',
                "This field is not available on {$dayNames[$date->dayOfWeek]}. Available: " . implode(', ', $allowed) . "."
            );
        }

        // Available hours — earliest start
        if ($field->available_start_time && $request->startTime < $field->available_start_time) {
            $result->addViolation(
                'field_availability',
                "Start time ({$this->fmt($request->startTime)}) is before this field opens ({$this->fmt(substr($field->available_start_time, 0, 5))})."
            );
        }

        // Available hours — latest end
        if ($field->available_end_time && $request->endTime > $field->available_end_time) {
            $result->addViolation(
                'field_availability',
                "End time ({$this->fmt($request->endTime)}) is after this field closes ({$this->fmt(substr($field->available_end_time, 0, 5))})."
            );
        }

        // Slot start interval alignment
        if ($field->slot_interval_minutes) {
            $startParts = explode(':', $request->startTime);
            $totalMinutes = (int)$startParts[0] * 60 + (int)$startParts[1];
            if ($totalMinutes % $field->slot_interval_minutes !== 0) {
                $result->addViolation(
                    'field_availability',
                    "Start time must align to {$field->slot_interval_minutes}-minute intervals (e.g., " . $this->exampleSlots($field->slot_interval_minutes) . ")."
                );
            }
        }

        // Field min duration
        $start = Carbon::parse($request->date . ' ' . $request->startTime);
        $end = Carbon::parse($request->date . ' ' . $request->endTime);
        $duration = $start->diffInMinutes($end);

        if ($field->min_event_minutes && $duration < $field->min_event_minutes) {
            $result->addViolation(
                'field_availability',
                "Event ({$duration} min) is shorter than this field's minimum of {$field->min_event_minutes} minutes."
            );
        }

        // Field max duration
        if ($field->max_event_minutes && $duration > $field->max_event_minutes) {
            $result->addViolation(
                'field_availability',
                "Event ({$duration} min) exceeds this field's maximum of {$field->max_event_minutes} minutes."
            );
        }
    }

    protected function exampleSlots(int $interval): string
    {
        $examples = [];
        for ($m = 0; $m < 60 && count($examples) < 4; $m += $interval) {
            $examples[] = sprintf(':d', $m);
        }
        return implode(', ', $examples);
    }
}
