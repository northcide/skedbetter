<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\BlackoutRule;
use App\Models\Field;
use App\Models\Location;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlackoutRuleController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);

        $rules = BlackoutRule::orderByDesc('start_date')->get();

        return Inertia::render('Leagues/Blackouts/Index', [
            'league' => $context->league(),
            'rules' => $rules,
            'userRole' => $context->userRole(),
        ]);
    }

    public function create(string $league)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Blackouts/Create', [
            'league' => $context->league(),
            'locations' => Location::with('fields')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'reason' => 'nullable|string|max:255',
            'scope_type' => 'required|in:league,location,field',
            'scope_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'recurrence' => 'required|in:none,weekly,yearly',
            'day_of_week' => 'nullable|integer|between:0,6',
        ]);

        $validated['league_id'] = $context->league()->id;

        // For league scope, set scope_id to league id
        if ($validated['scope_type'] === 'league') {
            $validated['scope_id'] = $context->league()->id;
        }

        BlackoutRule::create($validated);

        return redirect()->route('leagues.blackouts.index', $league)
            ->with('success', 'Blackout rule created successfully.');
    }

    public function edit(string $league, BlackoutRule $blackout)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Blackouts/Edit', [
            'league' => $context->league(),
            'rule' => $blackout,
            'locations' => Location::with('fields')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $league, BlackoutRule $blackout)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'reason' => 'nullable|string|max:255',
            'scope_type' => 'required|in:league,location,field',
            'scope_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'recurrence' => 'required|in:none,weekly,yearly',
            'day_of_week' => 'nullable|integer|between:0,6',
            'is_active' => 'boolean',
        ]);

        $blackout->update($validated);

        return redirect()->route('leagues.blackouts.index', $league)
            ->with('success', 'Blackout rule updated successfully.');
    }

    public function destroy(string $league, BlackoutRule $blackout)
    {
        $blackout->delete();

        return redirect()->route('leagues.blackouts.index', $league)
            ->with('success', 'Blackout rule deleted.');
    }
}
