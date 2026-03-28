<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Team;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
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

        $team->load(['division.season', 'users', 'scheduleEntries' => function ($q) {
            $q->active()->orderBy('date')->orderBy('start_time')->limit(20);
        }, 'scheduleEntries.field.location']);

        return Inertia::render('Leagues/Teams/Show', [
            'league' => $context->league(),
            'team' => $team,
            'userRole' => $context->userRole(),
        ]);
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
