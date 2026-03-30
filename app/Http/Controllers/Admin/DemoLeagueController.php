<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Field;
use App\Models\League;
use App\Models\Location;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DemoLeagueController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->isSuperadmin()) {
            abort(403);
        }

        return Inertia::render('Admin/DemoLeague');
    }

    public function store(Request $request)
    {
        if (!$request->user()->isSuperadmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'divisions' => 'required|integer|min:1|max:20',
            'teams_per_division' => 'required|integer|min:1|max:30',
            'locations' => 'required|integer|min:1|max:10',
            'fields_per_location' => 'required|integer|min:1|max:10',
        ]);

        $league = DB::transaction(function () use ($validated, $request) {
            $sport = $this->randomSport();
            $city = $this->randomCity();
            $leagueName = "{$city} Youth {$sport['name']} League";

            // Create league
            $league = League::create([
                'name' => $leagueName,
                'description' => "Demo league - {$leagueName}",
                'timezone' => 'America/New_York',
                'contact_email' => 'demo@skedbetter.com',
                'is_active' => true,
                'approved_at' => now(),
                'requested_by' => $request->user()->id,
                'stripe_plan' => 'pro',
            ]);

            // Attach superadmin
            $request->user()->leagues()->syncWithoutDetaching([
                $league->id => ['role' => 'league_admin', 'accepted_at' => now()],
            ]);

            // Create season
            $season = Season::create([
                'league_id' => $league->id,
                'name' => 'Spring ' . date('Y'),
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->addMonths(3)->endOfMonth()->toDateString(),
                'is_current' => true,
            ]);

            // Create locations & fields
            $parkNames = $this->shuffled($this->parkNames());
            $fieldNames = $sport['fields'];

            for ($li = 0; $li < $validated['locations']; $li++) {
                $location = Location::create([
                    'league_id' => $league->id,
                    'name' => $parkNames[$li % count($parkNames)] ?? "Park " . ($li + 1),
                    'city' => $city,
                    'state' => 'NY',
                    'is_active' => true,
                ]);

                for ($fi = 0; $fi < $validated['fields_per_location']; $fi++) {
                    $fieldLabel = $fieldNames[$fi % count($fieldNames)] ?? "Field " . ($fi + 1);
                    Field::create([
                        'location_id' => $location->id,
                        'league_id' => $league->id,
                        'name' => $fieldLabel . ' ' . ($fi + 1),
                        'is_active' => true,
                        'sort_order' => $fi,
                    ]);
                }
            }

            // Create divisions & teams with coaches
            $divisionNames = $this->divisionNames($sport['name']);
            $teamNames = $this->shuffled($sport['teams']);
            $coachNames = $this->shuffled($this->coachNames());
            $teamIdx = 0;
            $coachIdx = 0;
            $colors = $this->teamColors();

            for ($di = 0; $di < $validated['divisions']; $di++) {
                $divName = $divisionNames[$di % count($divisionNames)] ?? "Division " . ($di + 1);

                $division = Division::create([
                    'league_id' => $league->id,
                    'season_id' => $season->id,
                    'name' => $divName,
                    'sort_order' => $di,
                ]);

                for ($ti = 0; $ti < $validated['teams_per_division']; $ti++) {
                    $tName = $teamNames[$teamIdx % count($teamNames)] ?? "Team " . ($teamIdx + 1);
                    $coach = $coachNames[$coachIdx % count($coachNames)];
                    $coachEmail = strtolower(str_replace(' ', '.', $coach)) . '.' . Str::random(4) . '@demo.skedbetter.com';

                    Team::create([
                        'division_id' => $division->id,
                        'league_id' => $league->id,
                        'name' => $tName,
                        'color_code' => $colors[$teamIdx % count($colors)],
                        'contact_name' => $coach,
                        'contact_email' => $coachEmail,
                    ]);

                    $teamIdx++;
                    $coachIdx++;
                }
            }

            $league->update(['settings' => ['onboarding_completed' => true]]);

            return $league;
        });

        return back()->with('success', "Demo league \"{$league->name}\" created with {$validated['divisions']} divisions, " .
            ($validated['divisions'] * $validated['teams_per_division']) . " teams, {$validated['locations']} locations, and " .
            ($validated['locations'] * $validated['fields_per_location']) . " fields.");
    }

    private function randomSport(): array
    {
        $sports = [
            [
                'name' => 'Baseball',
                'fields' => ['Diamond', 'Field', 'Lot'],
                'teams' => ['Tigers', 'Eagles', 'Bears', 'Wolves', 'Hawks', 'Bulldogs', 'Panthers', 'Cobras', 'Falcons', 'Sharks', 'Vipers', 'Stallions', 'Cougars', 'Rattlers', 'Mustangs', 'Rockets', 'Thunder', 'Storm', 'Blazers', 'Knights', 'Hornets', 'Titans', 'Raptors', 'Mavericks', 'Cyclones', 'Sting', 'Wildcats', 'Rebels', 'Bandits', 'Comets'],
            ],
            [
                'name' => 'Soccer',
                'fields' => ['Pitch', 'Field', 'Turf'],
                'teams' => ['United', 'FC Lightning', 'Strikers', 'Rapids', 'Dynamo', 'FC Storm', 'Galaxy', 'Rovers', 'Aces', 'Blast', 'Fury', 'Heat', 'Impact', 'Kickers', 'Legends', 'Meteors', 'Flames', 'Scorpions', 'Warriors', 'Cosmos', 'Rush', 'Surge', 'Thunderbolts', 'Tornados', 'Chargers', 'Barracudas', 'Bombers', 'Crushers', 'Demons', 'Inferno'],
            ],
            [
                'name' => 'Softball',
                'fields' => ['Diamond', 'Field', 'Lot'],
                'teams' => ['Lightning', 'Firecrackers', 'Stingrays', 'Firebirds', 'Storm', 'Blaze', 'Fury', 'Thunder', 'Diamonds', 'Ice', 'Hurricanes', 'Crushers', 'Heat', 'Bombers', 'Rampage', 'Wildcats', 'Rebels', 'Bandits', 'Rockets', 'Panthers', 'Vipers', 'Cougars', 'Sting', 'Comets', 'Stars', 'Blazers', 'Hornets', 'Titans', 'Cyclones', 'Mavericks'],
            ],
            [
                'name' => 'Lacrosse',
                'fields' => ['Field', 'Turf', 'Pitch'],
                'teams' => ['Attack', 'Rattlers', 'Black Bears', 'Cannons', 'Chaos', 'Dragons', 'Eclipse', 'Enforcers', 'Gladiators', 'Gorillas', 'Grizzlies', 'Guardians', 'Hitmen', 'Lancers', 'Outlaws', 'Phoenix', 'Predators', 'Raiders', 'Rampage', 'Sabercats', 'Scorpions', 'Sentinels', 'Stingers', 'Surge', 'Tomahawks', 'Tribe', 'Trojans', 'Vikings', 'Warhawks', 'Wolfpack'],
            ],
        ];

        return $sports[array_rand($sports)];
    }

    private function randomCity(): string
    {
        $cities = [
            'Riverside', 'Springfield', 'Greenfield', 'Lakewood', 'Fairview',
            'Maplewood', 'Brookhaven', 'Cedar Falls', 'Pine Valley', 'Oakdale',
            'Willowbrook', 'Stonebridge', 'Clearwater', 'Meadowview', 'Ridgewood',
        ];

        return $cities[array_rand($cities)];
    }

    private function parkNames(): array
    {
        return [
            'Memorial Park', 'Veterans Field Complex', 'Riverside Park',
            'Lakeside Recreation Center', 'Community Sports Complex', 'Founders Park',
            'Heritage Field', 'Pine Grove Athletic Complex', 'Sunset Fields',
            'Eagle Ridge Park',
        ];
    }

    private function divisionNames(string $sport): array
    {
        return [
            "6U {$sport}", "8U {$sport}", "10U {$sport}", "12U {$sport}",
            "14U {$sport}", "16U {$sport}", "18U {$sport}",
            "6U {$sport} Gold", "8U {$sport} Gold", "10U {$sport} Gold",
            "12U {$sport} Gold", "14U {$sport} Gold",
            "Rookie League", "Minor League", "Major League",
            "Junior Varsity", "Varsity", "Travel A", "Travel B", "Rec League",
        ];
    }

    private function coachNames(): array
    {
        return [
            'Mike Johnson', 'Sarah Williams', 'Dave Thompson', 'Jennifer Martinez',
            'Chris Anderson', 'Amy Wilson', 'Brian Davis', 'Lisa Brown',
            'Tom Garcia', 'Karen Miller', 'Steve Rodriguez', 'Laura Taylor',
            'John Moore', 'Michelle Jackson', 'Rick White', 'Donna Harris',
            'Dan Martin', 'Sue Clark', 'Matt Lewis', 'Kim Robinson',
            'Jim Walker', 'Pam Hall', 'Rob Allen', 'Beth Young',
            'Mark King', 'Tracy Wright', 'Bob Scott', 'Jen Adams',
            'Paul Baker', 'Nicole Green', 'Jeff Nelson', 'Tina Carter',
            'Eric Mitchell', 'Sandy Perez', 'Greg Roberts', 'Lori Turner',
            'Ryan Phillips', 'Debbie Campbell', 'Tony Parker', 'Angela Edwards',
            'Kevin Collins', 'Heather Stewart', 'Doug Sanchez', 'Cindy Morris',
            'Phil Rogers', 'Diane Reed', 'Carl Cook', 'Wendy Morgan',
        ];
    }

    private function teamColors(): array
    {
        return [
            '#DC2626', '#EA580C', '#D97706', '#CA8A04',
            '#65A30D', '#16A34A', '#059669', '#0D9488',
            '#0891B2', '#0284C7', '#2563EB', '#4F46E5',
            '#7C3AED', '#9333EA', '#C026D3', '#DB2777',
            '#1D4ED8', '#B91C1C', '#047857', '#7C2D12',
            '#1E3A5F', '#4A1D6E', '#831843', '#713F12',
            '#0F766E', '#334155', '#78350F', '#365314',
            '#1E40AF', '#6D28D9',
        ];
    }

    private function shuffled(array $items): array
    {
        shuffle($items);
        return $items;
    }
}
