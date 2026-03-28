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

        $divisions = Division::with('season')
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
            'max_event_minutes' => 'nullable|integer|min:15|max:480',
        ]);

        Division::create($validated);

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', 'Division created successfully.');
    }

    public function edit(string $league, Division $division)
    {
        $context = app(LeagueContext::class);

        $seasons = Season::orderByDesc('start_date')->get();

        return Inertia::render('Leagues/Divisions/Edit', [
            'league' => $context->league(),
            'division' => $division,
            'seasons' => $seasons,
        ]);
    }

    public function update(Request $request, string $league, Division $division)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'season_id' => 'required|exists:seasons,id',
            'age_group' => 'nullable|string|max:255',
            'skill_level' => 'nullable|string|max:255',
        ]);

        $division->update($validated);

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', 'Division updated successfully.');
    }

    public function destroy(string $league, Division $division)
    {
        $division->delete();

        return redirect()->route('leagues.divisions.index', $league)
            ->with('success', 'Division deleted successfully.');
    }
}
