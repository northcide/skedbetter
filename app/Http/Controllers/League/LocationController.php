<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\Location;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationController extends Controller
{
    public function index(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $locations = Location::with(['fields' => function ($q) {
                $q->orderBy('sort_order')->with('allowedDivisions');
            }])
            ->withCount('fields')
            ->orderBy('name')
            ->get();

        return Inertia::render('Leagues/Locations/Index', [
            'league' => $context->league(),
            'locations' => $locations,
            'userRole' => $context->userRole(),
        ]);
    }

    public function create(string $league)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Locations/Create', [
            'league' => $context->league(),
        ]);
    }

    public function store(Request $request, string $league)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
        ]);

        Location::create($validated);

        return redirect()->route('leagues.locations.index', $league)
            ->with('success', 'Location created successfully.');
    }

    public function edit(string $league, Location $location)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Locations/Edit', [
            'league' => $context->league(),
            'location' => $location->load('fields'),
        ]);
    }

    public function update(Request $request, string $league, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $location->update($validated);

        return redirect()->route('leagues.locations.index', $league)
            ->with('success', 'Location updated successfully.');
    }

    public function destroy(string $league, Location $location)
    {
        $location->delete();

        return redirect()->route('leagues.locations.index', $league)
            ->with('success', 'Location deleted successfully.');
    }
}
