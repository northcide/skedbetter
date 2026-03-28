<?php

namespace App\Services\Scheduling;

use App\Models\BlackoutRule;
use App\Models\ScheduleEntry;
use App\Models\Team;
use App\Services\Scheduling\DTO\ConflictResult;
use App\Services\Scheduling\DTO\ScheduleRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConflictDetector
{
    public function check(ScheduleRequest $request): ConflictResult
    {
        $result = new ConflictResult();

        $this->checkFieldOverlap($request, $result);
        $this->checkTeamOverlap($request, $result);
        $this->checkBlackoutViolation($request, $result);
        $this->checkWeeklySlotLimit($request, $result);

        return $result;
    }

    protected function checkFieldOverlap(ScheduleRequest $request, ConflictResult $result): void
    {
        $query = ScheduleEntry::withoutGlobalScopes()
            ->where('field_id', $request->fieldId)
            ->where('date', $request->date)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $request->endTime)
            ->where('end_time', '>', $request->startTime);

        if ($request->excludeEntryId) {
            $query->where('id', '!=', $request->excludeEntryId);
        }

        $conflicts = $query->get();

        foreach ($conflicts as $conflict) {
            $teamName = $conflict->team?->name ?? 'Unknown';
            $result->addViolation(
                'field_overlap',
                "Field is already booked from {$conflict->start_time} to {$conflict->end_time} by {$teamName}."
            );
        }
    }

    protected function checkTeamOverlap(ScheduleRequest $request, ConflictResult $result): void
    {
        $query = ScheduleEntry::withoutGlobalScopes()
            ->where('team_id', $request->teamId)
            ->where('date', $request->date)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $request->endTime)
            ->where('end_time', '>', $request->startTime);

        if ($request->excludeEntryId) {
            $query->where('id', '!=', $request->excludeEntryId);
        }

        $conflicts = $query->with('field.location')->get();

        foreach ($conflicts as $conflict) {
            $fieldName = $conflict->field?->name ?? 'Unknown';
            $locationName = $conflict->field?->location?->name ?? '';
            $where = $locationName ? "{$fieldName} at {$locationName}" : $fieldName;
            $result->addViolation(
                'team_overlap',
                "Team is already scheduled from {$conflict->start_time} to {$conflict->end_time} at {$where}."
            );
        }
    }

    protected function checkBlackoutViolation(ScheduleRequest $request, ConflictResult $result): void
    {
        $date = Carbon::parse($request->date);

        $rules = BlackoutRule::withoutGlobalScopes()
            ->where('league_id', $request->leagueId)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if (! $this->ruleAppliesToScope($rule, $request)) {
                continue;
            }

            if (! $this->ruleAppliesToDate($rule, $date)) {
                continue;
            }

            if (! $this->ruleAppliesToTime($rule, $request->startTime, $request->endTime)) {
                continue;
            }

            $result->addViolation(
                'blackout',
                "Blocked by blackout rule \"{$rule->name}\": {$rule->reason}"
            );
        }
    }

    protected function ruleAppliesToScope(BlackoutRule $rule, ScheduleRequest $request): bool
    {
        return match ($rule->scope_type) {
            'league' => true,
            'location' => DB::table('fields')
                ->where('id', $request->fieldId)
                ->where('location_id', $rule->scope_id)
                ->exists(),
            'field' => $rule->scope_id === $request->fieldId,
            default => false,
        };
    }

    protected function ruleAppliesToDate(BlackoutRule $rule, Carbon $date): bool
    {
        return match ($rule->recurrence->value ?? $rule->recurrence) {
            'none' => $date->between($rule->start_date, $rule->end_date),
            'weekly' => $date->between($rule->start_date, $rule->end_date)
                && $date->dayOfWeek === $rule->day_of_week,
            'yearly' => $date->month === $rule->start_date->month
                && $date->day >= $rule->start_date->day
                && $date->day <= $rule->end_date->day,
            default => false,
        };
    }

    protected function ruleAppliesToTime(BlackoutRule $rule, string $startTime, string $endTime): bool
    {
        // If no time specified, the entire day is blacked out
        if (is_null($rule->start_time) && is_null($rule->end_time)) {
            return true;
        }

        // Time overlap check
        $ruleStart = $rule->start_time ?? '00:00:00';
        $ruleEnd = $rule->end_time ?? '23:59:59';

        return $startTime < $ruleEnd && $endTime > $ruleStart;
    }

    protected function checkWeeklySlotLimit(ScheduleRequest $request, ConflictResult $result): void
    {
        $team = Team::withoutGlobalScopes()->find($request->teamId);
        if (! $team) {
            return;
        }

        // Get the limit: team override, or check scheduling_constraints
        $limit = $team->max_weekly_slots;

        if (is_null($limit)) {
            $limit = $this->getConstraintValue($request, 'max_weekly_slots');
        }

        if (is_null($limit)) {
            return; // No limit configured
        }

        $date = Carbon::parse($request->date);
        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $date->copy()->endOfWeek(Carbon::SUNDAY);

        $query = ScheduleEntry::withoutGlobalScopes()
            ->where('team_id', $request->teamId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()]);

        if ($request->excludeEntryId) {
            $query->where('id', '!=', $request->excludeEntryId);
        }

        $count = $query->count();

        if ($count >= $limit) {
            $result->addViolation(
                'weekly_limit',
                "Team has reached the weekly limit of {$limit} scheduled slots (currently {$count} this week)."
            );
        }
    }

    protected function getConstraintValue(ScheduleRequest $request, string $type): ?int
    {
        $constraint = DB::table('scheduling_constraints')
            ->where('league_id', $request->leagueId)
            ->where('season_id', $request->seasonId)
            ->where('constraint_type', $type)
            ->where('is_active', true)
            ->orderByRaw("CASE scope_type WHEN 'team' THEN 1 WHEN 'division' THEN 2 WHEN 'league' THEN 3 ELSE 4 END")
            ->first();

        if (! $constraint) {
            return null;
        }

        $value = json_decode($constraint->value, true);
        return $value['max'] ?? $value['value'] ?? null;
    }
}
