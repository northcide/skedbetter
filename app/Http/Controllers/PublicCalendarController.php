<?php

namespace App\Http\Controllers;

use App\Models\BlackoutRule;
use App\Models\Division;
use App\Models\Field;
use App\Models\League;
use App\Models\Location;
use App\Models\ScheduleEntry;
use App\Models\Team;
use App\Services\LeagueContext;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicCalendarController extends Controller
{
    private function resolveLeague(string $token): League
    {
        return League::where('public_token', $token)
            ->where('is_active', true)
            ->whereNotNull('approved_at')
            ->firstOrFail();
    }

    private function setContext(League $league): void
    {
        app(LeagueContext::class)->set($league);
    }

    public function show(string $token)
    {
        $league = $this->resolveLeague($token);
        $this->setContext($league);

        return Inertia::render('Public/Calendar', [
            'league' => $league->only('id', 'name', 'timezone'),
            'teams' => Team::with('division:id,name')->orderBy('name')->get(),
            'divisions' => Division::orderBy('name')->get(['id', 'name']),
            'locations' => Location::with(['fields' => fn ($q) => $q->orderBy('sort_order')])->orderBy('name')->get(),
            'token' => $token,
            'weather' => app(WeatherService::class)->getCachedForecast($league),
        ]);
    }

    public function events(Request $request, string $token): JsonResponse
    {
        $league = $this->resolveLeague($token);
        $this->setContext($league);

        $query = ScheduleEntry::with(['team', 'field.location'])->active();

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
            $teamIds = Team::withoutGlobalScopes()
                ->where('division_id', $request->division_id)
                ->pluck('id');
            $query->whereIn('team_id', $teamIds);
        }
        if ($request->has('location_id')) {
            $fieldIds = Field::withoutGlobalScopes()
                ->where('location_id', $request->location_id)
                ->pluck('id');
            $query->whereIn('field_id', $fieldIds);
        }

        $events = $query->get()->map(fn ($entry) => [
            'id' => $entry->id,
            'title' => $entry->team->name . ' - ' . ucfirst($entry->type->value ?? $entry->type) . ($entry->title ? ': ' . $entry->title : ''),
            'start' => $entry->date->format('Y-m-d') . 'T' . $entry->start_time,
            'end' => $entry->date->format('Y-m-d') . 'T' . $entry->end_time,
            'resourceId' => $entry->field_id,
            'editable' => false,
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
            ],
        ]);

        // Blackout background events
        $blackoutEvents = collect();
        try {
            $blackouts = BlackoutRule::withoutGlobalScopes()
                ->where('league_id', $league->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->with('scopeEntries')
                ->get();

            $startDate = $request->has('start') ? Carbon::parse($request->start) : now()->startOfMonth();
            $endDate = $request->has('end') ? Carbon::parse($request->end) : now()->endOfMonth();

            foreach ($blackouts as $bo) {
                if (! $bo->start_date || ! $bo->end_date) continue;
                $dates = $this->expandBlackoutDates($bo, $startDate, $endDate);
                foreach ($dates as $date) {
                    $start = $bo->start_time ? $date . 'T' . $bo->start_time : $date;
                    $end = $bo->end_time ? $date . 'T' . $bo->end_time : $date . 'T23:59:59';
                    $allDay = ! $bo->start_time && ! $bo->end_time;

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
        } catch (\Exception $e) {
            // Don't let blackout errors break the calendar
        }

        return response()->json($events->concat($blackoutEvents)->values());
    }

    public function resources(string $token): JsonResponse
    {
        $league = $this->resolveLeague($token);
        $this->setContext($league);

        $fields = Field::with('location')
            ->where('is_active', true)
            ->whereNotNull('location_id')
            ->orderBy('location_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($f) => $f->location !== null);

        $locations = $fields->pluck('location')->filter()->unique('id');

        $resources = $locations->map(fn ($location) => [
            'id' => 'loc-' . $location->id,
            'title' => $location->name,
        ])->values();

        $fieldResources = $fields->map(fn ($field) => [
            'id' => $field->id,
            'title' => $field->name,
            'parentId' => 'loc-' . $field->location_id,
        ]);

        return response()->json($resources->concat($fieldResources)->values());
    }

    private function expandBlackoutDates(BlackoutRule $bo, $rangeStart, $rangeEnd): array
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
}
