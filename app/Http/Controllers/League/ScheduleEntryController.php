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

    public function calendar(string $league)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Schedule/Calendar', [
            'league' => $context->league(),
            'userRole' => $context->userRole(),
            'teams' => Team::with('division')->orderBy('name')->get(),
            'seasons' => Season::orderByDesc('start_date')->get(),
            'divisions' => \App\Models\Division::with('season')->orderBy('name')->get(),
            'locations' => \App\Models\Location::with(['fields' => fn($q) => $q->orderBy('sort_order')])->orderBy('name')->get(),
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
            return back()->withErrors(['conflicts' => $result->getAllMessages()]);
        }

        return redirect()->route('leagues.schedule.index', $league)
            ->with('success', 'Schedule entry updated successfully.');
    }

    public function destroy(string $league, ScheduleEntry $scheduleEntry)
    {
        $this->schedulingService->cancel($scheduleEntry, request()->user()->id);

        return redirect()->route('leagues.schedule.index', $league)
            ->with('success', 'Schedule entry cancelled.');
    }

    // API endpoint for FullCalendar
    public function events(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

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

        return response()->json($entries->map(function ($entry) {
            return [
                'id' => $entry->id,
                'title' => $entry->title ?: $entry->team->name . ' - ' . ucfirst($entry->type->value ?? $entry->type),
                'start' => $entry->date->format('Y-m-d') . 'T' . $entry->start_time,
                'end' => $entry->date->format('Y-m-d') . 'T' . $entry->end_time,
                'resourceId' => $entry->field_id,
                'backgroundColor' => $entry->team->color_code ?? '#3B82F6',
                'borderColor' => $entry->team->color_code ?? '#3B82F6',
                'extendedProps' => [
                    'team_id' => $entry->team_id,
                    'team_name' => $entry->team->name,
                    'field_name' => $entry->field->name,
                    'location_name' => $entry->field->location->name,
                    'type' => $entry->type->value ?? $entry->type,
                    'status' => $entry->status->value ?? $entry->status,
                    'notes' => $entry->notes,
                ],
            ];
        }));
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

        return redirect()->route('leagues.schedule.index', $league)
            ->with('success', $message)
            ->with('bulk_result', $result);
    }

    public function cancelSeries(Request $request, string $league, ScheduleEntry $scheduleEntry)
    {
        if (! $scheduleEntry->recurrence_group_id) {
            return back()->with('error', 'This entry is not part of a series.');
        }

        $cancelled = $this->bulkScheduler->deleteFutureInGroup(
            $scheduleEntry->recurrence_group_id,
            $scheduleEntry->date->toDateString(),
            $request->user()->id
        );

        return redirect()->route('leagues.schedule.index', $league)
            ->with('success', "Cancelled {$cancelled} entries in this series.");
    }
}
