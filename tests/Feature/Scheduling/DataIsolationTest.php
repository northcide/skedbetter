<?php

use App\Models\Division;
use App\Models\Field;
use App\Models\League;
use App\Models\Location;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Services\LeagueContext;

test('league scoping isolates data between leagues', function () {
    $user = User::factory()->create();

    // Create two leagues
    $leagueA = League::create(['name' => 'League A', 'slug' => 'league-a', 'timezone' => 'America/Chicago']);
    $leagueB = League::create(['name' => 'League B', 'slug' => 'league-b', 'timezone' => 'America/Chicago']);

    $seasonA = Season::create(['league_id' => $leagueA->id, 'name' => 'Season A', 'start_date' => '2026-03-01', 'end_date' => '2026-06-30']);
    $seasonB = Season::create(['league_id' => $leagueB->id, 'name' => 'Season B', 'start_date' => '2026-03-01', 'end_date' => '2026-06-30']);

    $locA = Location::create(['league_id' => $leagueA->id, 'name' => 'Park A']);
    $locB = Location::create(['league_id' => $leagueB->id, 'name' => 'Park B']);

    $divA = Division::create(['league_id' => $leagueA->id, 'season_id' => $seasonA->id, 'name' => 'Div A']);
    $divB = Division::create(['league_id' => $leagueB->id, 'season_id' => $seasonB->id, 'name' => 'Div B']);

    $teamA = Team::create(['division_id' => $divA->id, 'league_id' => $leagueA->id, 'name' => 'Team A']);
    $teamB = Team::create(['division_id' => $divB->id, 'league_id' => $leagueB->id, 'name' => 'Team B']);

    // Set context to League A
    $context = app(LeagueContext::class);
    $context->set($leagueA);

    // League A should only see its own data
    expect(Season::all())->toHaveCount(1);
    expect(Season::first()->name)->toBe('Season A');

    expect(Location::all())->toHaveCount(1);
    expect(Location::first()->name)->toBe('Park A');

    expect(Division::all())->toHaveCount(1);
    expect(Division::first()->name)->toBe('Div A');

    expect(Team::all())->toHaveCount(1);
    expect(Team::first()->name)->toBe('Team A');

    // Switch to League B
    $context->set($leagueB);

    expect(Season::all())->toHaveCount(1);
    expect(Season::first()->name)->toBe('Season B');

    expect(Team::all())->toHaveCount(1);
    expect(Team::first()->name)->toBe('Team B');
});
