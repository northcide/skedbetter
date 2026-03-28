<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\ScheduleEntry;
use App\Models\Season;
use App\Models\Team;
use App\Services\LeagueContext;
use App\Services\Scheduling\BulkScheduler;
use App\Services\Scheduling\DTO\ConflictResult;
use App\Services\Scheduling\SchedulingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScheduleEntryController extends Controller
{
    public function __construct(
        protected SchedulingService $schedulingService,
        protected BulkScheduler $bulkScheduler,
    ) {}

    public function calendar(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $user = $request->user();
        $coachTeamIds = $user->teams()->pluck('teams.id')->toArray();

        return Inertia::render('Leagues/Schedule/Calendar', [
            'league' => $context->league(),
            'userRole' => $context->userRole(),
            'teams' => Team::with('division')->orderBy('name')->get(),
            'seasons' => Season::orderByDesc('start_date')->get(),
            'divisions' => \App\Models\Division::with('season')->orderBy('name')->get(),
            'locations' => \App\Models\Location::with(['fields' => fn($q) => $q->orderBy('sort_order')])->orderBy('name')->get(),
            'coachTeamIds' => $coachTeamIds,
        ]);
    }

    public function index(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $query = ScheduleEntry::with(['field.location', 'team', 'season'])
            ->orderBy('date')
            ->orderBy('start_time');

        if ($request->has('season_id')) {
            $query->where('season_id', $request->season_id);
        }

        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        if ($request->has('field_id')) {
            $query->where('field_id', $request->field_id);
        }

        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $entries = $query->paginate(50);

        return Inertia::render('Leagues/Schedule/Index', [
            'league' => $context->league(),
            'entries' => $entries,
            'seasons' => Season::orderByDesc('start_date')->get(),
            'teams' => Team::orderBy('name')->get(),
            'fields' => Field::with('location')->orderBy('name')->get(),
            'filters' => $request->only(['season_id', 'team_id', 'field_id', 'date_from', 'date_to']),
            'userRole' => $context->userRole(),
        ]);
    }

    public function create(string $league)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Schedule/Create', [
            'league' => $context->league(),
            'seasons' => Season::orderByDesc('start_date')->get(),
            'teams' => Team::with('division')->orderBy('name')->get(),
            'fields' => Field::with('location')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'field_id' => 'required|exists:fields,id',
            'team_id' => 'required|exists:teams,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'type' => 'required|in:practice,game,scrimmage,tournament,other',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['league_id'] = $context->league()->id;

        // Coaches can only schedule their own teams
        $role = $context->userRole();
        if ($role === 'coach') {
            $coachTeamIds = $request->user()->teams()->pluck('teams.id')->map(fn($id) => (int) $id)->toArray();
            if (! in_array((int) $validated['team_id'], $coachTeamIds)) {
                $error = 'You can only schedule your own teams.';
                return $request->wantsJson()
                    ? response()->json(['errors' => ['conflicts' => [$error]]], 422)
                    : back()->withErrors(['conflicts' => [$error]]);
            }
        }

        $result = $this->schedulingService->create($validated, $request->user()->id);

        if ($result instanceof ConflictResult) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => ['conflicts' => $result->getAllMessages()]], 422);
            }
            return back()->withErrors(['conflicts' => $result->getAllMessages()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'id' => $result->id]);
        }

        return redirect()->route('leagues.schedule.index', $league)
            ->with('success', 'Schedule entry created successfully.');
    }

    public function edit(string $league, ScheduleEntry $scheduleEntry)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Schedule/Edit', [
            'league' => $context->league(),
            'entry' => $scheduleEntry->load(['field.location', 'team']),
            'seasons' => Season::orderByDesc('start_date')->get(),
            'teams' => Team::with('division')->orderBy('name')->get(),
            'fields' => Field::with('location')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $league, ScheduleEntry $scheduleEntry)
    {
        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'field_id' => 'required|exists:fields,id',
            'team_id' => 'required|exists:teams,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'type' => 'required|in:practice,game,scrimmage,tournament,other',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:confirmed,tentative,cancelled',
        ]);

        $result = $this->schedulingService->update($scheduleEntry, $validated, $request->user()->id);

        if ($result instanceof ConflictResult) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => ['conflicts' => $result->getAllMessages()]], 422);
            }
            return back()->withErrors(['conflicts' => $result->getAllMessages()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('leagues.schedule.index', $league)
            ->with('success', 'Schedule entry updated successfully.');
    }

    public function validateEntry(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'field_id' => 'required|exists:fields,id',
            'team_id' => 'required|exists:teams,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'exclude_entry_id' => 'nullable|integer',
        ]);

        $season = Season::where('is_current', true)->first();

        $scheduleRequest = \App\Services\Scheduling\DTO\ScheduleRequest::fromArray([
            'league_id' => $context->league()->id,
            'season_id' => $season?->id ?? 0,
            'field_id' => $validated['field_id'],
            'team_id' => $validated['team_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'exclude_entry_id' => $validated['exclude_entry_id'] ?? null,
        ]);

        $result = $this->schedulingService->validate($scheduleRequest);

        return response()->json([
            'valid' => ! $result->hasConflicts(),
            'errors' => $result->getAllMessages(),
        ]);
    }

    public function destroy(string $league, ScheduleEntry $scheduleEntry)
    {
        $this->schedulingService->cancel($scheduleEntry, request()->user()->id);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('leagues.schedule.index', $league)
            ->with('success', 'Schedule entry deleted.');
    }

    // API endpoint for FullCalendar
    public function events(Request $request, string $league)
    {
        $context = app(LeagueContext::class);
        $user = $request->user();
        $role = $context->userRole();
        $isManager = in_array($role, ['superadmin', 'league_admin', 'division_manager']);
        $coachTeamIds = $isManager ? [] : $user->teams()->pluck('teams.id')->toArray();

        $query = ScheduleEntry::with(['team', 'field.location'])
            ->active();

        if ($request->has('start')) {
            $query->where('date', '>=', $request->start);
        }
        if ($request->has('end')) {
            $query->where('date', '<=', $request->end);
        }
        if ($request->has('field_id')) {
            $query->where('field_id', $request->field_id);
        }
        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }
        if ($request->has('division_id')) {
            $teamIds = \App\Models\Team::withoutGlobalScopes()
                ->where('division_id', $request->division_id)
                ->pluck('id');
            $query->whereIn('team_id', $teamIds);
        }
        if ($request->has('location_id')) {
            $fieldIds = \App\Models\Field::withoutGlobalScopes()
                ->where('location_id', $request->location_id)
                ->pluck('id');
            $query->whereIn('field_id', $fieldIds);
        }

        $entries = $query->get();

        return response()->json($entries->map(function ($entry) use ($isManager, $coachTeamIds) {
            $canEdit = $isManager || in_array($entry->team_id, $coachTeamIds);
            return [
                'id' => $entry->id,
                'title' => $entry->title ?: $entry->team->name . ' - ' . ucfirst($entry->type->value ?? $entry->type),
                'start' => $entry->date->format('Y-m-d') . 'T' . $entry->start_time,
                'end' => $entry->date->format('Y-m-d') . 'T' . $entry->end_time,
                'resourceId' => $entry->field_id,
                'editable' => $canEdit,
                'backgroundColor' => $entry->team->color_code ?? '#3B82F6',
                'borderColor' => $entry->team->color_code ?? '#3B82F6',
                'extendedProps' => [
                    'team_id' => $entry->team_id,
                    'field_id' => $entry->field_id,
                    'team_name' => $entry->team->name,
                    'field_name' => $entry->field->name,
                    'location_name' => $entry->field->location->name,
                    'type' => $entry->type->value ?? $entry->type,
                    'status' => $entry->status->value ?? $entry->status,
                    'notes' => $entry->notes,
                ],
            ];
        });

        // Append blackout rules as background events
        $blackouts = \App\Models\BlackoutRule::withoutGlobalScopes()
            ->where('league_id', $context->league()->id)
            ->where('is_active', true)
            ->get();

        $startDate = $request->has('start') ? \Carbon\Carbon::parse($request->start) : now()->startOfMonth();
        $endDate = $request->has('end') ? \Carbon\Carbon::parse($request->end) : now()->endOfMonth();

        $blackoutEvents = collect();
        foreach ($blackouts as $bo) {
            $dates = $this->expandBlackoutDates($bo, $startDate, $endDate);
            foreach ($dates as $date) {
                $start = $bo->start_time ? $date . 'T' . $bo->start_time : $date;
                $end = $bo->end_time ? $date . 'T' . $bo->end_time : $date . 'T23:59:59';
                $allDay = !$bo->start_time && !$bo->end_time;

                $blackoutEvents->push([
                    'id' => 'blackout-' . $bo->id . '-' . $date,
                    'title' => $bo->name,
                    'start' => $allDay ? $date : $start,
                    'end' => $allDay ? $date : $end,
                    'allDay' => $allDay,
                    'display' => 'background',
                    'backgroundColor' => '#f3f4f6',
                    'borderColor' => '#d1d5db',
                    'editable' => false,
                    'extendedProps' => ['is_blackout' => true, 'reason' => $bo->reason],
                ]);
            }
        }

        return response()->json($events->concat($blackoutEvents)->values());
    }

    protected function expandBlackoutDates(\App\Models\BlackoutRule $bo, $rangeStart, $rangeEnd): array
    {
        $dates = [];
        $recurrence = $bo->recurrence->value ?? $bo->recurrence ?? 'none';

        if ($recurrence === 'none') {
            $current = $bo->start_date->copy();
            while ($current->lte($bo->end_date) && $current->lte($rangeEnd)) {
                if ($current->gte($rangeStart)) {
                    $dates[] = $current->toDateString();
                }
                $current->addDay();
            }
        } elseif ($recurrence === 'weekly') {
            $current = $rangeStart->copy();
            while ($current->lte($rangeEnd)) {
                if ($current->dayOfWeek === $bo->day_of_week && $current->between($bo->start_date, $bo->end_date)) {
                    $dates[] = $current->toDateString();
                }
                $current->addDay();
            }
        } elseif ($recurrence === 'yearly') {
            $current = $rangeStart->copy();
            while ($current->lte($rangeEnd)) {
                if ($current->month === $bo->start_date->month && $current->day >= $bo->start_date->day && $current->day <= $bo->end_date->day) {
                    $dates[] = $current->toDateString();
                }
                $current->addDay();
            }
        }

        return $dates;
    }

    // API endpoint for FullCalendar resources (fields)
    public function resources(string $league)
    {
        $fields = Field::with('location')
            ->where('is_active', true)
            ->orderBy('location_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json($fields->map(function ($field) {
            return [
                'id' => $field->id,
                'title' => $field->name,
                'parentId' => 'loc-' . $field->location_id,
                'extendedProps' => [
                    'location' => $field->location->name,
                    'surface_type' => $field->surface_type?->value,
                    'is_lighted' => $field->is_lighted,
                ],
            ];
        })->prepend(
            // Add location groups as parent resources
            ...$fields->pluck('location')->unique('id')->map(function ($location) {
                return [
                    'id' => 'loc-' . $location->id,
                    'title' => $location->name,
                ];
            })->values()
        ));
    }

    // API endpoint for drag-drop updates
    public function move(Request $request, string $league, ScheduleEntry $scheduleEntry)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'field_id' => 'required|exists:fields,id',
        ]);

        $result = $this->schedulingService->update($scheduleEntry, $validated, $request->user()->id);

        if ($result instanceof ConflictResult) {
            return response()->json([
                'success' => false,
                'errors' => $result->getAllMessages(),
            ], 422);
        }

        return response()->json(['success' => true]);
    }

    public function bulk(string $league)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Schedule/Bulk', [
            'league' => $context->league(),
            'seasons' => Season::orderByDesc('start_date')->get(),
            'teams' => Team::with('division')->orderBy('name')->get(),
            'fields' => Field::with('location')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function bulkStore(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'field_id' => 'required|exists:fields,id',
            'team_id' => 'required|exists:teams,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'type' => 'required|in:practice,game,scrimmage,tournament,other',
            'title' => 'nullable|string|max:255',
            'frequency' => 'required|in:weekly,biweekly',
            'until' => 'required|date|after:date',
        ]);

        $baseData = [
            'league_id' => $context->league()->id,
            'season_id' => $validated['season_id'],
            'field_id' => $validated['field_id'],
            'team_id' => $validated['team_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
        ];

        $pattern = [
            'frequency' => $validated['frequency'],
            'until' => $validated['until'],
        ];

        $result = $this->bulkScheduler->createRecurring($baseData, $pattern, $request->user()->id);

        $message = "Created {$result['created']} of {$result['total']} entries.";
        if (count($result['skipped']) > 0) {
            $message .= ' ' . count($result['skipped']) . ' skipped due to conflicts.';
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'result' => $result]);
        }

        return redirect()->route('leagues.schedule.index', $league)
            ->with('success', $message)
            ->with('bulk_result', $result);
    }

    public function cancelSeries(Request $request, string $league, ScheduleEntry $scheduleEntry)
    {
        if (! $scheduleEntry->recurrence_group_id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'This entry is not part of a series.'], 422);
            }
            return back()->with('error', 'This entry is not part of a series.');
        }

        $cancelled = $this->bulkScheduler->deleteFutureInGroup(
            $scheduleEntry->recurrence_group_id,
            $scheduleEntry->date->toDateString(),
            $request->user()->id
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'cancelled' => $cancelled]);
        }

        return redirect()->route('leagues.schedule.index', $league)
            ->with('success', "Cancelled {$cancelled} entries in this series.");
    }
}
