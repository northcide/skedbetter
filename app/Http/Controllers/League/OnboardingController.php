<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Mail\MagicLinkMail;
use App\Models\Division;
use App\Models\Field;
use App\Models\Location;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);

        $adminEmail = session("league_{$context->league()->id}_admin_email", $context->league()->contact_email);

        return Inertia::render('Leagues/Onboarding/Index', [
            'league' => $context->league(),
            'userRole' => $context->userRole(),
            'adminEmail' => $adminEmail,
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
            'send_invite' => 'boolean',
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

            // Create or find the league admin user and attach to league
            $adminEmail = session("league_{$l->id}_admin_email", $l->contact_email);
            if ($adminEmail) {
                $adminUser = User::firstOrCreate(
                    ['email' => strtolower(trim($adminEmail))],
                    ['name' => explode('@', $adminEmail)[0], 'password' => bcrypt(\Str::random(32))]
                );

                // Attach as league_admin if not already
                if (! $l->users()->where('users.id', $adminUser->id)->wherePivot('role', 'league_admin')->exists()) {
                    $l->users()->attach($adminUser->id, ['role' => 'league_admin', 'accepted_at' => now()]);
                }
            }
        });

        // Send invite email if requested
        $adminEmail = session("league_{$l->id}_admin_email", $l->contact_email);
        if ($validated['send_invite'] ?? false && $adminEmail) {
            try {
                $magicLink = MagicLinkController::generateForUser($adminEmail, $request->user()->id);
                Mail::to($adminEmail)->send(new MagicLinkMail($magicLink));
            } catch (\Exception $e) {
                // Don't fail setup if email fails
            }
        }

        session()->forget("league_{$l->id}_admin_email");

        return redirect()->route('leagues.show', $l->slug)
            ->with('success', 'League setup complete!');
    }
}
