<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Mail\MagicLinkMail;
use App\Models\BlackoutRule;
use App\Models\Division;
use App\Models\Field;
use App\Models\SchedulingConstraint;
use App\Models\Team;
use App\Models\User;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);

        $teams = Team::with(['division'])
            ->orderBy('division_id')
            ->orderBy('name')
            ->get();

        $divisions = Division::orderBy('name')->get();

        return Inertia::render('Leagues/Teams/Index', [
            'league' => $context->league(),
            'teams' => $teams,
            'divisions' => $divisions,
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

        $team = Team::create($validated);

        if (! empty($validated['contact_email'])) {
            $this->associateCoach($team, $validated['contact_email'], $validated['contact_name'] ?? null);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'team' => $team]);
        }

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', 'Team created successfully.');
    }

    public function bulkStore(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'teams' => 'required|array|min:1',
            'teams.*.name' => 'required|string|max:255',
            'teams.*.contact_name' => 'nullable|string|max:255',
            'teams.*.contact_email' => 'nullable|email',
        ]);

        $count = 0;
        foreach ($validated['teams'] as $t) {
            if (empty($t['name'])) continue;
            $team = Team::create([
                'division_id' => $validated['division_id'],
                'league_id' => $context->league()->id,
                'name' => $t['name'],
                'contact_name' => $t['contact_name'] ?? null,
                'contact_email' => $t['contact_email'] ?? null,
            ]);
            if (! empty($t['contact_email'])) {
                $this->associateCoach($team, $t['contact_email'], $t['contact_name'] ?? null);
            }
            $count++;
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'count' => $count]);
        }

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', "{$count} team(s) added.");
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
        if ($division && $division->scheduling_priority) {
            $rules[] = [
                'source' => 'Division',
                'sourceDetail' => $division->name,
                'rule' => "Default scheduling priority: {$division->scheduling_priority} (1=highest)",
                'type' => 'constraint',
            ];
        }

        // 2. Field access restrictions — which fields this team's division can/cannot use
        $restrictedFields = DB::table('division_field')
            ->join('fields', 'fields.id', '=', 'division_field.field_id')
            ->join('locations', 'locations.id', '=', 'fields.location_id')
            ->where('division_field.division_id', $divisionId)
            ->select('fields.id', 'fields.name as field_name', 'locations.name as location_name',
                'division_field.max_weekly_slots', 'division_field.priority',
                'division_field.booking_window_type', 'division_field.booking_opens_date', 'division_field.booking_opens_days')
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

        // Fields where this division IS allowed
        $divPriority = $division?->scheduling_priority;
        foreach ($restrictedFields as $rf) {
            $parts = [];
            $effectivePriority = $rf->priority ?: $divPriority;
            if ($effectivePriority) {
                $label = "Priority {$effectivePriority}";
                if ($rf->priority && $divPriority && $rf->priority != $divPriority) {
                    $label .= " (override)";
                } elseif (!$rf->priority && $divPriority) {
                    $label .= " (division default)";
                }
                $parts[] = $label;
            }
            if ($rf->booking_window_type === 'calendar' && $rf->booking_opens_date) {
                $opens = \Carbon\Carbon::parse($rf->booking_opens_date);
                $parts[] = $opens->isPast() ? 'booking open' : "opens {$opens->format('M j')}";
            } elseif ($rf->booking_window_type === 'rolling' && $rf->booking_opens_days) {
                $parts[] = "books {$rf->booking_opens_days}d ahead";
            }
            if ($rf->max_weekly_slots) $parts[] = "max {$rf->max_weekly_slots}/wk";

            $rule = ! empty($parts) ? implode(', ', $parts) : 'Allowed';

            $rules[] = [
                'source' => 'Field',
                'sourceDetail' => "{$rf->field_name} @ {$rf->location_name}",
                'rule' => $rule,
                'type' => $rf->max_weekly_slots ? 'field_weekly_limit' : 'field_allowed',
            ];

        }

        // 2b. Field availability rules (days, hours, slot rules)
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $allFields = DB::table('fields')
            ->join('locations', 'locations.id', '=', 'fields.location_id')
            ->where('fields.league_id', $league->id)
            ->whereNull('fields.deleted_at')
            ->where('fields.is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('available_days')
                    ->orWhereNotNull('available_start_time')
                    ->orWhereNotNull('available_end_time')
                    ->orWhereNotNull('slot_interval_minutes')
                    ->orWhereNotNull('min_event_minutes')
                    ->orWhereNotNull('max_event_minutes');
            })
            ->select('fields.*', 'locations.name as location_name')
            ->get();

        foreach ($allFields as $f) {
            $parts = [];
            $days = $f->available_days ? json_decode($f->available_days, true) : null;
            if ($days) $parts[] = implode(', ', array_map(fn($d) => $dayNames[$d] ?? $d, $days)) . ' only';
            if ($f->available_start_time) $parts[] = 'opens ' . substr($f->available_start_time, 0, 5);
            if ($f->available_end_time) $parts[] = 'closes ' . substr($f->available_end_time, 0, 5);
            if ($f->slot_interval_minutes) $parts[] = "starts every {$f->slot_interval_minutes}m";
            if ($f->min_event_minutes) $parts[] = "min {$f->min_event_minutes}m";
            if ($f->max_event_minutes) $parts[] = "max {$f->max_event_minutes}m";

            if (! empty($parts)) {
                $rules[] = [
                    'source' => 'Field',
                    'sourceDetail' => "{$f->name} @ {$f->location_name}",
                    'rule' => implode(' · ', $parts),
                    'type' => 'constraint',
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
            'send_invite' => 'nullable|boolean',
        ]);

        $sendInvite = $validated['send_invite'] ?? false;
        unset($validated['send_invite']);

        $team->update($validated);

        // Auto-create/associate coach if email provided
        if (! empty($validated['contact_email'])) {
            $this->associateCoach($team, $validated['contact_email'], $validated['contact_name'] ?? null);
        }

        // Send invite if requested
        $inviteSent = false;
        if ($sendInvite && ! empty($validated['contact_email'])) {
            try {
                $magicLink = MagicLinkController::generateForUser($validated['contact_email'], $request->user()->id);
                Mail::to($validated['contact_email'])->send(new MagicLinkMail($magicLink));
                $inviteSent = true;
            } catch (\Exception $e) {
                // Silently fail — save still succeeded
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'invite_sent' => $inviteSent]);
        }

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', 'Team updated successfully.' . ($inviteSent ? ' Invite sent.' : ''));
    }

    public function sendInvite(Request $request, string $league, Team $team)
    {
        if (! $team->contact_email) {
            return response()->json(['success' => false, 'message' => 'No coach email set.'], 422);
        }

        // Ensure coach user exists and is associated
        $this->associateCoach($team, $team->contact_email, $team->contact_name);

        try {
            $magicLink = MagicLinkController::generateForUser($team->contact_email, $request->user()->id);
            Mail::to($team->contact_email)->send(new MagicLinkMail($magicLink));
            return response()->json(['success' => true, 'message' => "Invite sent to {$team->contact_email}."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => "Failed to send: {$e->getMessage()}"], 500);
        }
    }

    public function destroy(Request $request, string $league, Team $team)
    {
        $team->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', 'Team deleted successfully.');
    }

    protected function associateCoach(Team $team, string $email, ?string $name = null): void
    {
        $email = strtolower(trim($email));

        // Create or find user
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name ?: explode('@', $email)[0], 'password' => bcrypt(\Str::random(32)), 'approved_at' => now()]
        );

        // Auto-approve if created by a manager and not yet approved
        if (!$user->approved_at) {
            $user->update(['approved_at' => now()]);
        }

        // Update name if provided and user had a generated name
        if ($name && $user->name === explode('@', $email)[0]) {
            $user->update(['name' => $name]);
        }

        // Add to team as coach if not already
        if (! $team->users()->where('users.id', $user->id)->exists()) {
            $team->users()->attach($user->id, ['role' => 'coach']);
        }

        // Add to league as coach if not already a member
        $league = $team->league;
        if ($league && ! $league->users()->where('users.id', $user->id)->exists()) {
            $league->users()->attach($user->id, ['role' => 'coach', 'accepted_at' => now()]);
        }
    }
}
