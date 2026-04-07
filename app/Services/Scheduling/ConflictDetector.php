<?php

namespace App\Services\Scheduling;

use App\Models\BlackoutRule;
use App\Models\Division;
use App\Models\DivisionAvailabilityRule;
use App\Models\Field;
use App\Models\ScheduleEntry;
use App\Models\Team;
use App\Services\Scheduling\DTO\ConflictResult;
use App\Services\Scheduling\DTO\ScheduleRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConflictDetector
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

    protected bool $isAdmin = false;

    public function asAdmin(bool $admin = true): self
    {
        $this->isAdmin = $admin;
        return $this;
    }

    public function check(ScheduleRequest $request): ConflictResult
    {
        $result = new ConflictResult();

        $this->checkBookingWindow($request, $result);
        $this->checkDivisionAvailability($request, $result);
        $this->checkFieldDivisionAccess($request, $result);
        $this->checkFieldOverlap($request, $result);
        $this->checkTeamOverlap($request, $result);
        $this->checkBlackoutViolation($request, $result);
        $this->checkWeeklySlotLimit($request, $result);
        $this->checkDivisionFieldWeeklyLimit($request, $result);

        return $result;
    }

    protected function checkDivisionAvailability(ScheduleRequest $request, ConflictResult $result): void
    {
        $team = Team::withoutGlobalScopes()->find($request->teamId);
        if (! $team) return;

        $rules = DivisionAvailabilityRule::where('division_id', $team->division_id)->get();

        // No rules = unrestricted
        if ($rules->isEmpty()) return;

        $date = Carbon::parse($request->date);
        $dayOfWeek = $date->dayOfWeek; // 0=Sun, 6=Sat
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $rule = $rules->firstWhere('day_of_week', $dayOfWeek);

        $divisionName = DB::table('divisions')->where('id', $team->division_id)->value('name') ?? 'Unknown';

        if (! $rule) {
            $allowedDays = $rules->pluck('day_of_week')->map(fn($d) => $dayNames[$d])->implode(', ');
            $msg = "Division \"{$divisionName}\" is not allowed to book on {$dayNames[$dayOfWeek]}. Allowed days: {$allowedDays}.";
            if ($this->isAdmin) {
                $result->addWarning('division_availability', $msg);
            } else {
                $result->addViolation('division_availability', $msg);
            }
            return;
        }

        // Day is allowed — check time window if not all-day
        if (! $rule->all_day && $rule->start_time && $rule->end_time) {
            $ruleStart = substr($rule->start_time, 0, 5);
            $ruleEnd = substr($rule->end_time, 0, 5);

            if ($request->startTime < $ruleStart || $request->endTime > $ruleEnd) {
                $msg = "Division \"{$divisionName}\" can only book between {$this->fmt($ruleStart)} and {$this->fmt($ruleEnd)} on {$dayNames[$dayOfWeek]}.";
                if ($this->isAdmin) {
                    $result->addWarning('division_availability', $msg);
                } else {
                    $result->addViolation('division_availability', $msg);
                }
            }
        }
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
                "Field is already booked from {$this->fmt($conflict->start_time)} to {$this->fmt($conflict->end_time)} by {$teamName}."
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
                "Team is already scheduled from {$this->fmt($conflict->start_time)} to {$this->fmt($conflict->end_time)} at {$where}."
            );
        }
    }

    protected function checkBlackoutViolation(ScheduleRequest $request, ConflictResult $result): void
    {
        $date = Carbon::parse($request->date);

        $rules = BlackoutRule::withoutGlobalScopes()
            ->where('league_id', $request->leagueId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
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
        return $rule->appliesToField($request->fieldId);
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

        // Get the limit: team override > division setting > scheduling_constraints
        $limit = $team->max_weekly_slots;

        if (is_null($limit)) {
            $division = DB::table('divisions')->where('id', $team->division_id)->first();
            if ($division && $division->max_weekly_events_per_team) {
                $limit = $division->max_weekly_events_per_team;
            }
        }

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

    protected function checkFieldDivisionAccess(ScheduleRequest $request, ConflictResult $result): void
    {
        $field = Field::withoutGlobalScopes()->find($request->fieldId);
        if (! $field) {
            return;
        }

        $team = Team::withoutGlobalScopes()->find($request->teamId);
        if (! $team) {
            return;
        }

        // Check 1: If the FIELD has restrictions, the division must be in the list
        $fieldRestrictions = DB::table('division_field')
            ->where('field_id', $request->fieldId)
            ->pluck('division_id')
            ->toArray();

        if (! empty($fieldRestrictions) && ! in_array($team->division_id, $fieldRestrictions)) {
            $divisionName = DB::table('divisions')->where('id', $team->division_id)->value('name') ?? 'Unknown';
            $msg = "Field \"{$field->name}\" is not available to the \"{$divisionName}\" division.";
            if ($this->isAdmin) {
                $result->addWarning('field_access', $msg);
            } else {
                $result->addViolation('field_access', $msg);
            }
            return;
        }

        // Check 2: If the DIVISION has any field assignments, it can ONLY use those fields
        $divisionFields = DB::table('division_field')
            ->where('division_id', $team->division_id)
            ->pluck('field_id')
            ->toArray();

        if (! empty($divisionFields) && ! in_array($request->fieldId, $divisionFields)) {
            $divisionName = DB::table('divisions')->where('id', $team->division_id)->value('name') ?? 'Unknown';
            $msg = "The \"{$divisionName}\" division can only schedule on its assigned fields.";
            if ($this->isAdmin) {
                $result->addWarning('field_access', $msg);
            } else {
                $result->addViolation('field_access', $msg);
            }
        }
    }

    protected function checkDivisionFieldWeeklyLimit(ScheduleRequest $request, ConflictResult $result): void
    {
        $team = Team::withoutGlobalScopes()->find($request->teamId);
        if (! $team) {
            return;
        }

        // Check if there's a weekly limit for this division on this field
        $pivot = DB::table('division_field')
            ->where('division_id', $team->division_id)
            ->where('field_id', $request->fieldId)
            ->first();

        if (! $pivot || is_null($pivot->max_weekly_slots)) {
            return; // No per-field weekly limit
        }

        $date = Carbon::parse($request->date);
        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $date->copy()->endOfWeek(Carbon::SUNDAY);

        // Count all entries for teams in this division on this field this week
        $divisionTeamIds = DB::table('teams')
            ->where('division_id', $team->division_id)
            ->pluck('id')
            ->toArray();

        $query = ScheduleEntry::withoutGlobalScopes()
            ->where('field_id', $request->fieldId)
            ->whereIn('team_id', $divisionTeamIds)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()]);

        if ($request->excludeEntryId) {
            $query->where('id', '!=', $request->excludeEntryId);
        }

        $count = $query->count();

        if ($count >= $pivot->max_weekly_slots) {
            $divisionName = DB::table('divisions')->where('id', $team->division_id)->value('name') ?? 'Unknown';
            $result->addViolation(
                'division_field_limit',
                "Division \"{$divisionName}\" has reached the weekly limit of {$pivot->max_weekly_slots} slots on this field (currently {$count} this week)."
            );
        }
    }

    protected function checkBookingWindow(ScheduleRequest $request, ConflictResult $result): void
    {
        $team = Team::withoutGlobalScopes()->find($request->teamId);
        if (! $team) return;

        $division = Division::withoutGlobalScopes()->with('bookingWindow')->find($team->division_id);
        if (! $division || ! $division->bookingWindow) return;

        $window = $division->bookingWindow;
        if (! $window->isOpenForDate($request->date)) {
            $msg = "Booking not yet open for {$division->name} ({$window->name}: {$window->opensDescription()})";
            if ($this->isAdmin) {
                $result->addWarning('booking_window', $msg);
            } else {
                $result->addViolation('booking_window', $msg);
            }
        }
    }
}
