<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\BlackoutRule;
use App\Models\Division;
use App\Models\Field;
use App\Models\SchedulingConstraint;
use App\Models\Team;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);

        $teams = Team::with('division.season')
            ->orderBy('name')
            ->get();

        return Inertia::render('Leagues/Teams/Index', [
            'league' => $context->league(),
            'teams' => $teams,
            'userRole' => $context->userRole(),
        ]);
    }

    public function create(string $league)
    {
        $context = app(LeagueContext::class);

        $divisions = Division::with('season')
            ->orderBy('name')
            ->get();

        return Inertia::render('Leagues/Teams/Create', [
            'league' => $context->league(),
            'divisions' => $divisions,
        ]);
    }

    public function store(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'division_id' => 'required|exists:divisions,id',
            'color_code' => 'nullable|string|max:7',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        $validated['league_id'] = $context->league()->id;

        Team::create($validated);

        return redirect()->route('leagues.teams.index', $league)
            ->with('success', 'Team created successfully.');
    }

    public function show(string $league, Team $team)
    {
        $context = app(LeagueContext::class);
        $leagueModel = $context->league();

        $team->load(['division.season', 'users', 'scheduleEntries' => function ($q) {
            $q->active()->orderBy('date')->orderBy('start_time')->limit(20);
        }, 'scheduleEntries.field.location']);

        return Inertia::render('Leagues/Teams/Show', [
            'league' => $leagueModel,
            'team' => $team,
            'userRole' => $context->userRole(),
            'icalUrl' => URL::signedRoute('ical.team', ['teamId' => $team->id]),
            'applicableRules' => $this->gatherApplicableRules($team, $leagueModel),
        ]);
    }

    protected function gatherApplicableRules(Team $team, $league): array
    {
        $rules = [];
        $divisionId = $team->division_id;

        // 1. Team-level: max weekly slots
        if ($team->max_weekly_slots) {
            $rules[] = [
                'source' => 'Team',
                'sourceDetail' => $team->name,
                'rule' => "Max {$team->max_weekly_slots} scheduled slots per week",
                'type' => 'weekly_limit',
            ];
        }

        // 1b. Division rules
        $division = $team->division;
        if ($division && $division->max_event_minutes) {
            $rules[] = [
                'source' => 'Division',
                'sourceDetail' => $division->name,
                'rule' => "Max event duration: {$division->max_event_minutes} minutes",
                'type' => 'constraint',
            ];
        }
        if ($division && $division->max_weekly_events_per_team) {
            $rules[] = [
                'source' => 'Division',
                'sourceDetail' => $division->name,
                'rule' => "Max {$division->max_weekly_events_per_team} events per team per week",
                'type' => 'weekly_limit',
            ];
        }

        // 2. Field access restrictions — which fields this team's division can/cannot use
        $restrictedFields = DB::table('division_field')
            ->join('fields', 'fields.id', '=', 'division_field.field_id')
            ->join('locations', 'locations.id', '=', 'fields.location_id')
            ->where('division_field.division_id', $divisionId)
            ->select('fields.id', 'fields.name as field_name', 'locations.name as location_name', 'division_field.max_weekly_slots')
            ->get();

        // Check if ANY fields have restrictions (meaning some fields exclude this division)
        $allFieldsWithRestrictions = DB::table('division_field')
            ->select('field_id')
            ->distinct()
            ->pluck('field_id')
            ->toArray();

        // Fields that have restrictions but this division is NOT in the list
        $blockedFieldIds = array_diff(
            $allFieldsWithRestrictions,
            $restrictedFields->pluck('id')->toArray()
        );

        if (! empty($blockedFieldIds)) {
            $blockedFields = DB::table('fields')
                ->join('locations', 'locations.id', '=', 'fields.location_id')
                ->whereIn('fields.id', $blockedFieldIds)
                ->whereNull('fields.deleted_at')
                ->select('fields.name as field_name', 'locations.name as location_name')
                ->get();

            foreach ($blockedFields as $bf) {
                $rules[] = [
                    'source' => 'Field',
                    'sourceDetail' => "{$bf->field_name} @ {$bf->location_name}",
                    'rule' => 'Not available to this division',
                    'type' => 'field_blocked',
                ];
            }
        }

        // Fields where this division IS allowed with weekly limits
        foreach ($restrictedFields as $rf) {
            if ($rf->max_weekly_slots) {
                $rules[] = [
                    'source' => 'Field',
                    'sourceDetail' => "{$rf->field_name} @ {$rf->location_name}",
                    'rule' => "Division limited to {$rf->max_weekly_slots} slot(s)/week on this field",
                    'type' => 'field_weekly_limit',
                ];
            } else {
                $rules[] = [
                    'source' => 'Field',
                    'sourceDetail' => "{$rf->field_name} @ {$rf->location_name}",
                    'rule' => 'Allowed (no weekly limit)',
                    'type' => 'field_allowed',
                ];
            }
        }

        // 3. Blackout rules (league-wide)
        $blackouts = BlackoutRule::where('league_id', $league->id)
            ->where('is_active', true)
            ->orderBy('start_date')
            ->get();

        foreach ($blackouts as $bo) {
            $scope = match ($bo->scope_type) {
                'league' => 'All fields',
                'location' => DB::table('locations')->where('id', $bo->scope_id)->value('name') ?? 'Location',
                'field' => DB::table('fields')
                    ->join('locations', 'locations.id', '=', 'fields.location_id')
                    ->where('fields.id', $bo->scope_id)
                    ->selectRaw("CONCAT(fields.name, ' @ ', locations.name) as label")
                    ->value('label') ?? 'Field',
                default => 'Unknown',
            };

            $dateRange = $bo->start_date->format('M j') .
                ($bo->start_date->ne($bo->end_date) ? ' - ' . $bo->end_date->format('M j') : '');

            $timeRange = ($bo->start_time && $bo->end_time)
                ? substr($bo->start_time, 0, 5) . '-' . substr($bo->end_time, 0, 5)
                : 'All day';

            $recurrence = match ($bo->recurrence->value ?? $bo->recurrence) {
                'weekly' => ' (weekly)',
                'yearly' => ' (yearly)',
                default => '',
            };

            $rules[] = [
                'source' => 'Blackout',
                'sourceDetail' => $scope,
                'rule' => "{$bo->name}: {$dateRange} {$timeRange}{$recurrence}",
                'type' => 'blackout',
            ];
        }

        // 4. Scheduling constraints (league/division/team scoped)
        $currentSeason = $league->currentSeason;
        if ($currentSeason) {
            $constraints = SchedulingConstraint::where('league_id', $league->id)
                ->where('season_id', $currentSeason->id)
                ->where('is_active', true)
                ->get();

            foreach ($constraints as $c) {
                // Only show if it applies to this team/division or is league-wide
                $applies = match ($c->scope_type) {
                    'team' => $c->scope_id === $team->id,
                    'division' => $c->scope_id === $divisionId,
                    'league', null => true,
                    default => false,
                };

                if (! $applies) continue;

                $scopeLabel = match ($c->scope_type) {
                    'team' => 'Team-specific',
                    'division' => 'Division',
                    'league', null => 'League-wide',
                    default => 'Unknown',
                };

                $value = $c->value;
                $description = match ($c->constraint_type->value ?? $c->constraint_type) {
                    'max_weekly_slots' => "Max {$value['max']} slots per week",
                    'time_block_length' => 'Allowed block lengths: ' . implode(', ', (array)($value['minutes'] ?? [])) . ' min',
                    'earliest_start_time' => "Earliest start: {$value['time']}",
                    'latest_end_time' => "Latest end: {$value['time']}",
                    'allowed_days_of_week' => 'Allowed days: ' . implode(', ', array_map(
                        fn($d) => ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$d] ?? $d,
                        $value['days'] ?? []
                    )),
                    'min_gap_between_slots' => "Min {$value['minutes']} min gap between slots",
                    'max_daily_slots' => "Max {$value['max']} slots per day",
                    default => json_encode($value),
                };

                $rules[] = [
                    'source' => 'Constraint',
                    'sourceDetail' => $scopeLabel,
                    'rule' => $description,
                    'type' => 'constraint',
                ];
            }
        }

        return $rules;
    }

    public function edit(string $league, Team $team)
    {
        $context = app(LeagueContext::class);

        $divisions = Division::with('season')
            ->orderBy('name')
            ->get();

        return Inertia::render('Leagues/Teams/Edit', [
            'league' => $context->league(),
            'team' => $team,
            'divisions' => $divisions,
        ]);
    }

    public function update(Request $request, string $league, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'division_id' => 'required|exists:divisions,id',
            'color_code' => 'nullable|string|max:7',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'max_weekly_slots' => 'nullable|integer|min:1',
        ]);

        $team->update($validated);

        return redirect()->route('leagues.teams.index', $league)
            ->with('success', 'Team updated successfully.');
    }

    public function destroy(string $league, Team $team)
    {
        $team->delete();

        return redirect()->route('leagues.teams.index', $league)
            ->with('success', 'Team deleted successfully.');
    }
}
