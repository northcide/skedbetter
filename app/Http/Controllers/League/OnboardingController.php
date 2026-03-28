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
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Onboarding/Index', [
            'league' => $context->league(),
            'userRole' => $context->userRole(),
        ]);
    }

    public function save(Request $request, string $league)
    {
        $context = app(LeagueContext::class);
        $l = $context->league();

        $validated = $request->validate([
            'season' => 'required|array',
            'season.name' => 'required|string|max:255',
            'season.start_date' => 'required|date',
            'season.end_date' => 'required|date|after:season.start_date',
            'locations' => 'required|array|min:1',
            'locations.*.name' => 'required|string|max:255',
            'locations.*.address' => 'nullable|string|max:255',
            'locations.*.city' => 'nullable|string|max:255',
            'locations.*.state' => 'nullable|string|max:2',
            'locations.*.fields' => 'required|array|min:1',
            'locations.*.fields.*.name' => 'required|string|max:255',
            'divisions' => 'required|array|min:1',
            'divisions.*.name' => 'required|string|max:255',
            'divisions.*.age_group' => 'nullable|string|max:255',
            'divisions.*.teams' => 'nullable|array',
            'divisions.*.teams.*.name' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $l) {
            // Season
            Season::where('league_id', $l->id)->where('is_current', true)->update(['is_current' => false]);
            $season = Season::create([
                'league_id' => $l->id,
                'name' => $validated['season']['name'],
                'start_date' => $validated['season']['start_date'],
                'end_date' => $validated['season']['end_date'],
                'is_current' => true,
            ]);

            // Locations + Fields
            foreach ($validated['locations'] as $locData) {
                $location = Location::create([
                    'league_id' => $l->id,
                    'name' => $locData['name'],
                    'address' => $locData['address'] ?? null,
                    'city' => $locData['city'] ?? null,
                    'state' => $locData['state'] ?? null,
                ]);

                foreach ($locData['fields'] as $fieldData) {
                    Field::create([
                        'location_id' => $location->id,
                        'league_id' => $l->id,
                        'name' => $fieldData['name'],
                    ]);
                }
            }

            // Divisions + Teams
            foreach ($validated['divisions'] as $divData) {
                $division = Division::create([
                    'league_id' => $l->id,
                    'season_id' => $season->id,
                    'name' => $divData['name'],
                    'age_group' => $divData['age_group'] ?? null,
                ]);

                foreach ($divData['teams'] ?? [] as $teamData) {
                    Team::create([
                        'division_id' => $division->id,
                        'league_id' => $l->id,
                        'name' => $teamData['name'],
                    ]);
                }
            }

            // Mark onboarding complete
            $settings = $l->settings ?? [];
            $settings['onboarding_completed'] = true;
            $l->update(['settings' => $settings]);
        });

        return redirect()->route('leagues.show', $l->slug)
            ->with('success', 'League setup complete!');
    }
}
