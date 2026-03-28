<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Enums\SurfaceType;
use App\Models\Field;
use App\Models\Location;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FieldController extends Controller
{
    public function create(string $league, Location $location)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Fields/Create', [
            'league' => $context->league(),
            'location' => $location,
            'surfaceTypes' => array_column(SurfaceType::cases(), 'value'),
        ]);
    }

    public function store(Request $request, string $league, Location $location)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surface_type' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
            'is_lighted' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['location_id'] = $location->id;
        $validated['league_id'] = $context->league()->id;

        Field::create($validated);

        return redirect()->route('leagues.locations.edit', [$league, $location])
            ->with('success', 'Field created successfully.');
    }

    public function edit(string $league, Field $field)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Fields/Edit', [
            'league' => $context->league(),
            'field' => $field->load('location'),
            'surfaceTypes' => array_column(SurfaceType::cases(), 'value'),
        ]);
    }

    public function update(Request $request, string $league, Field $field)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surface_type' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
            'is_lighted' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $field->update($validated);

        return redirect()->route('leagues.locations.edit', [$league, $field->location])
            ->with('success', 'Field updated successfully.');
    }

    public function destroy(string $league, Field $field)
    {
        $locationId = $field->location_id;
        $field->delete();

        return redirect()->route('leagues.locations.edit', [$league, $locationId])
            ->with('success', 'Field deleted successfully.');
    }
}
