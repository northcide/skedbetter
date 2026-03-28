<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Field;
use App\Models\Location;
use App\Models\Season;
use App\Models\Team;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);
        $l = $context->league();

        $step = $this->determineStep($l);

        return Inertia::render('Leagues/Onboarding/Index', [
            'league' => $l,
            'currentStep' => $step,
            'seasons' => Season::orderByDesc('start_date')->get(),
            'locations' => Location::withCount('fields')->orderBy('name')->get(),
            'divisions' => Division::with('season')->withCount('teams')->orderBy('name')->get(),
            'teams' => Team::with('division')->orderBy('name')->get(),
        ]);
    }

    public function storeSeason(Request $request, string $league)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $validated['is_current'] = true;

        // Unset other current seasons
        Season::where('is_current', true)->update(['is_current' => false]);
        Season::create($validated);

        return back()->with('success', 'Season created! Now add your locations.');
    }

    public function storeLocation(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10',
            'fields' => 'required|array|min:1',
            'fields.*.name' => 'required|string|max:255',
        ]);

        $location = Location::create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'zip' => $validated['zip'] ?? null,
        ]);

        foreach ($validated['fields'] as $fieldData) {
            Field::create([
                'location_id' => $location->id,
                'league_id' => $context->league()->id,
                'name' => $fieldData['name'],
            ]);
        }

        return back()->with('success', "Added {$location->name} with " . count($validated['fields']) . " field(s).");
    }

    public function storeDivision(Request $request, string $league)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'season_id' => 'required|exists:seasons,id',
            'age_group' => 'nullable|string|max:255',
        ]);

        Division::create($validated);

        return back()->with('success', "Division \"{$validated['name']}\" created.");
    }

    public function storeTeams(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'teams' => 'required|array|min:1',
            'teams.*.name' => 'required|string|max:255',
        ]);

        foreach ($validated['teams'] as $teamData) {
            Team::create([
                'division_id' => $validated['division_id'],
                'league_id' => $context->league()->id,
                'name' => $teamData['name'],
            ]);
        }

        return back()->with('success', 'Added ' . count($validated['teams']) . ' team(s).');
    }

    public function complete(string $league)
    {
        $context = app(LeagueContext::class);
        $l = $context->league();

        $settings = $l->settings ?? [];
        $settings['onboarding_completed'] = true;
        $l->update(['settings' => $settings]);

        return redirect()->route('leagues.show', $l->slug)
            ->with('success', 'Setup complete! Your league is ready to start scheduling.');
    }

    protected function determineStep($league): int
    {
        if (Season::count() === 0) return 1;
        if (Location::count() === 0) return 2;
        if (Division::count() === 0) return 3;
        if (Team::count() === 0) return 4;
        return 5; // Ready to complete
    }
}
