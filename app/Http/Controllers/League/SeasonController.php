<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeasonController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);

        $seasons = Season::withCount('divisions')
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Leagues/Seasons/Index', [
            'league' => $context->league(),
            'seasons' => $seasons,
            'userRole' => $context->userRole(),
        ]);
    }

    public function create(string $league)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Seasons/Create', [
            'league' => $context->league(),
        ]);
    }

    public function store(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
        ]);

        // If marking as current, unset other current seasons
        if ($validated['is_current'] ?? false) {
            Season::where('is_current', true)->update(['is_current' => false]);
        }

        Season::create($validated);

        return redirect()->route('leagues.seasons.index', $league)
            ->with('success', 'Season created successfully.');
    }

    public function edit(string $league, Season $season)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Seasons/Edit', [
            'league' => $context->league(),
            'season' => $season,
        ]);
    }

    public function update(Request $request, string $league, Season $season)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_current' => 'boolean',
        ]);

        if ($validated['is_current'] ?? false) {
            Season::where('is_current', true)
                ->where('id', '!=', $season->id)
                ->update(['is_current' => false]);
        }

        $season->update($validated);

        return redirect()->route('leagues.seasons.index', $league)
            ->with('success', 'Season updated successfully.');
    }

    public function destroy(string $league, Season $season)
    {
        $season->delete();

        return redirect()->route('leagues.seasons.index', $league)
            ->with('success', 'Season deleted successfully.');
    }
}
