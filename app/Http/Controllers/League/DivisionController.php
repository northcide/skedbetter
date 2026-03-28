<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Season;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DivisionController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);

        $divisions = Division::with([
                'season',
                'managers:id,name,email',
                'teams' => fn($q) => $q->orderBy('name'),
                'teams.users' => fn($q) => $q->wherePivot('role', 'coach')->select('users.id', 'users.name', 'users.email'),
            ])
            ->withCount('teams')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Leagues/Divisions/Index', [
            'league' => $context->league(),
            'divisions' => $divisions,
            'userRole' => $context->userRole(),
        ]);
    }

    public function create(string $league)
    {
        $context = app(LeagueContext::class);

        $seasons = Season::orderByDesc('start_date')->get();

        return Inertia::render('Leagues/Divisions/Create', [
            'league' => $context->league(),
            'seasons' => $seasons,
        ]);
    }

    public function store(Request $request, string $league)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'season_id' => 'required|exists:seasons,id',
            'age_group' => 'nullable|string|max:255',
            'skill_level' => 'nullable|string|max:255',
            'max_event_minutes' => 'nullable|integer|in:30,60,90,120',
            'max_weekly_events_per_team' => 'nullable|integer|min:1|max:20',
        ]);

        Division::create($validated);

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', 'Division created successfully.');
    }

    public function bulkStore(Request $request, string $league)
    {
        $context = app(LeagueContext::class);
        $season = Season::where('is_current', true)->first();

        $validated = $request->validate([
            'divisions' => 'required|array|min:1',
            'divisions.*.name' => 'required|string|max:255',
            'divisions.*.age_group' => 'nullable|string|max:255',
        ]);

        $count = 0;
        foreach ($validated['divisions'] as $div) {
            if (empty($div['name'])) continue;
            Division::create([
                'league_id' => $context->league()->id,
                'season_id' => $season?->id,
                'name' => $div['name'],
                'age_group' => $div['age_group'] ?? null,
            ]);
            $count++;
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'count' => $count]);
        }

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', "{$count} division(s) added.");
    }

    public function edit(string $league, Division $division)
    {
        $context = app(LeagueContext::class);

        $division->load('allowedFields');

        return Inertia::render('Leagues/Divisions/Edit', [
            'league' => $context->league(),
            'division' => $division,
            'seasons' => Season::orderByDesc('start_date')->get(),
            'fields' => \App\Models\Field::with('location')->where('is_active', true)->orderBy('name')->get(),
            'allowedFieldIds' => $division->allowedFields->pluck('id')->toArray(),
            'userRole' => $context->userRole(),
        ]);
    }

    public function update(Request $request, string $league, Division $division)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'season_id' => 'required|exists:seasons,id',
            'age_group' => 'nullable|string|max:255',
            'skill_level' => 'nullable|string|max:255',
            'max_event_minutes' => 'nullable|integer|in:30,60,90,120',
            'max_weekly_events_per_team' => 'nullable|integer|min:1|max:20',
            'field_access' => 'nullable|in:all,specific',
            'allowed_field_ids' => 'nullable|array',
            'allowed_field_ids.*' => 'exists:fields,id',
        ]);

        $fieldAccess = $validated['field_access'] ?? null;
        $allowedFieldIds = $validated['allowed_field_ids'] ?? [];
        unset($validated['field_access'], $validated['allowed_field_ids']);

        $division->update($validated);

        // Sync field access from the division side
        if ($fieldAccess === 'all') {
            // Remove this division from all field restrictions
            $division->allowedFields()->detach();
        } elseif ($fieldAccess === 'specific') {
            // Sync: add this division to selected fields, remove from others
            $division->allowedFields()->sync($allowedFieldIds);
        }

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', 'Division updated.');
    }

    public function destroy(string $league, Division $division)
    {
        $division->delete();

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', 'Division deleted successfully.');
    }
}
