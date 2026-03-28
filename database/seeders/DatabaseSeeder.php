<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Field;
use App\Models\League;
use App\Models\Location;
use App\Models\ScheduleEntry;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Superadmin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@skedbetter.com',
            'password' => bcrypt('password'),
            'is_superadmin' => true,
            'email_verified_at' => now(),
        ]);

        // League Manager
        $manager = User::create([
            'name' => 'League Manager',
            'email' => 'manager@skedbetter.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Coach
        $coach = User::create([
            'name' => 'Coach Smith',
            'email' => 'coach@skedbetter.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // League
        $league = League::create([
            'name' => 'Metro Youth Soccer League',
            'slug' => 'metro-youth-soccer',
            'description' => 'Youth soccer league for the metropolitan area',
            'timezone' => 'America/Chicago',
            'contact_email' => 'info@metroyouthsoccer.com',
            'settings' => ['onboarding_completed' => true],
        ]);

        $league->users()->attach($admin->id, ['role' => 'league_manager', 'accepted_at' => now()]);
        $league->users()->attach($manager->id, ['role' => 'league_manager', 'accepted_at' => now()]);

        // Season
        $season = Season::create([
            'league_id' => $league->id,
            'name' => 'Spring 2026',
            'start_date' => '2026-03-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);

        // Locations & Fields
        $centralPark = Location::create([
            'league_id' => $league->id,
            'name' => 'Central Park Complex',
            'address' => '100 Main Street',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62701',
        ]);

        $fields = [];
        foreach (['Field A', 'Field B', 'Field C', 'Field D'] as $i => $name) {
            $fields[] = Field::create([
                'location_id' => $centralPark->id,
                'league_id' => $league->id,
                'name' => $name,
                'surface_type' => $i < 2 ? 'grass' : 'turf',
                'is_lighted' => $i >= 2,
                'sort_order' => $i,
            ]);
        }

        $riverside = Location::create([
            'league_id' => $league->id,
            'name' => 'Riverside Athletic Center',
            'address' => '500 River Road',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62702',
        ]);

        foreach (['North Field', 'South Field'] as $i => $name) {
            $fields[] = Field::create([
                'location_id' => $riverside->id,
                'league_id' => $league->id,
                'name' => $name,
                'surface_type' => 'turf',
                'is_lighted' => true,
                'sort_order' => $i,
            ]);
        }

        // Divisions
        $u8 = Division::create(['league_id' => $league->id, 'season_id' => $season->id, 'name' => 'U8 Co-Ed', 'age_group' => 'Under 8', 'sort_order' => 1]);
        $u10 = Division::create(['league_id' => $league->id, 'season_id' => $season->id, 'name' => 'U10 Boys', 'age_group' => 'Under 10', 'sort_order' => 2]);
        $u10g = Division::create(['league_id' => $league->id, 'season_id' => $season->id, 'name' => 'U10 Girls', 'age_group' => 'Under 10', 'sort_order' => 3]);
        $u12 = Division::create(['league_id' => $league->id, 'season_id' => $season->id, 'name' => 'U12 Boys', 'age_group' => 'Under 12', 'sort_order' => 4]);

        // Teams
        $teamNames = [
            $u8->id => ['Lightning', 'Thunder', 'Rockets', 'Stars'],
            $u10->id => ['Eagles', 'Hawks', 'Falcons', 'Wolves'],
            $u10g->id => ['Panthers', 'Tigers', 'Lions', 'Jaguars'],
            $u12->id => ['Strikers', 'United', 'FC Metro', 'Dynamo'],
        ];

        $colors = ['#EF4444', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316',
                    '#6366F1', '#14B8A6', '#E11D48', '#84CC16', '#A855F7', '#0EA5E9', '#D946EF', '#22C55E'];
        $colorIdx = 0;
        $allTeams = [];

        foreach ($teamNames as $divId => $names) {
            foreach ($names as $name) {
                $team = Team::create([
                    'division_id' => $divId,
                    'league_id' => $league->id,
                    'name' => $name,
                    'color_code' => $colors[$colorIdx % count($colors)],
                    'contact_name' => "Coach {$name}",
                    'contact_email' => strtolower($name) . '@example.com',
                ]);
                $allTeams[] = $team;
                $colorIdx++;
            }
        }

        // Assign coach to first team
        $allTeams[0]->users()->attach($coach->id, ['role' => 'coach']);
        $league->users()->attach($coach->id, ['role' => 'coach', 'accepted_at' => now()]);

        // Schedule entries — create some sample schedules
        $startDate = Carbon::parse('2026-04-06'); // First Monday in April

        foreach ($allTeams as $i => $team) {
            $fieldIdx = $i % count($fields);
            $field = $fields[$fieldIdx];

            // 4 weeks of practice
            for ($week = 0; $week < 4; $week++) {
                $date = $startDate->copy()->addWeeks($week);
                // Spread across days: practices on weekdays
                $dayOffset = ($i % 5); // Mon-Fri
                $practiceDate = $date->copy()->addDays($dayOffset);

                // Spread times: alternate between morning and evening slots
                $hour = ($i % 2 === 0) ? 17 : 18;
                $startTime = sprintf('%02d:00', $hour);
                $endTime = sprintf('%02d:00', $hour + 1);

                ScheduleEntry::withoutEvents(function () use ($team, $field, $practiceDate, $startTime, $endTime, $league, $season, $admin) {
                    ScheduleEntry::create([
                        'league_id' => $league->id,
                        'season_id' => $season->id,
                        'field_id' => $field->id,
                        'team_id' => $team->id,
                        'date' => $practiceDate->toDateString(),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'type' => 'practice',
                        'status' => 'confirmed',
                        'created_by' => $admin->id,
                    ]);
                });
            }
        }

        $this->command->info('Seeded: 3 users, 1 league, 1 season, 4 divisions, 16 teams, 2 locations, 6 fields, ' . (16 * 4) . ' schedule entries');
    }
}
