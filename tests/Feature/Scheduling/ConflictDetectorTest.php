<?php

use App\Models\Division;
use App\Models\Field;
use App\Models\League;
use App\Models\Location;
use App\Models\ScheduleEntry;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Models\BlackoutRule;
use App\Services\Scheduling\ConflictDetector;
use App\Services\Scheduling\DTO\ScheduleRequest;

beforeEach(function () {
    $this->user = User::factory()->create(['is_superadmin' => true]);
    $this->league = League::create(['name' => 'Test League', 'slug' => 'test-league', 'timezone' => 'America/Chicago']);
    $this->season = Season::create(['league_id' => $this->league->id, 'name' => 'Spring', 'start_date' => '2026-03-01', 'end_date' => '2026-06-30', 'is_current' => true]);
    $this->location = Location::create(['league_id' => $this->league->id, 'name' => 'Test Park']);
    $this->field = Field::create(['location_id' => $this->location->id, 'league_id' => $this->league->id, 'name' => 'Field A']);
    $this->field2 = Field::create(['location_id' => $this->location->id, 'league_id' => $this->league->id, 'name' => 'Field B']);
    $this->division = Division::create(['league_id' => $this->league->id, 'season_id' => $this->season->id, 'name' => 'U10']);
    $this->team = Team::create(['division_id' => $this->division->id, 'league_id' => $this->league->id, 'name' => 'Eagles']);
    $this->team2 = Team::create(['division_id' => $this->division->id, 'league_id' => $this->league->id, 'name' => 'Hawks']);

    $this->detector = new ConflictDetector();
});

test('no conflict when field is free', function () {
    $request = new ScheduleRequest(
        leagueId: $this->league->id,
        seasonId: $this->season->id,
        fieldId: $this->field->id,
        teamId: $this->team->id,
        date: '2026-04-15',
        startTime: '17:00',
        endTime: '18:00',
    );

    $result = $this->detector->check($request);
    expect($result->hasConflicts())->toBeFalse();
});

test('detects field time overlap', function () {
    ScheduleEntry::withoutEvents(function () {
        ScheduleEntry::create([
            'league_id' => $this->league->id, 'season_id' => $this->season->id,
            'field_id' => $this->field->id, 'team_id' => $this->team2->id,
            'date' => '2026-04-15', 'start_time' => '17:00', 'end_time' => '18:00',
            'type' => 'practice', 'status' => 'confirmed', 'created_by' => $this->user->id,
        ]);
    });

    $request = new ScheduleRequest(
        leagueId: $this->league->id, seasonId: $this->season->id,
        fieldId: $this->field->id, teamId: $this->team->id,
        date: '2026-04-15', startTime: '17:30', endTime: '18:30',
    );

    $result = $this->detector->check($request);
    expect($result->hasConflicts())->toBeTrue();
    expect($result->getViolations())->toHaveKey('field_overlap');
});

test('no conflict on different field', function () {
    ScheduleEntry::withoutEvents(fn() => ScheduleEntry::create([
        'league_id' => $this->league->id, 'season_id' => $this->season->id,
        'field_id' => $this->field->id, 'team_id' => $this->team2->id,
        'date' => '2026-04-15', 'start_time' => '17:00', 'end_time' => '18:00',
        'type' => 'practice', 'status' => 'confirmed', 'created_by' => $this->user->id,
    ]));

    $request = new ScheduleRequest(
        leagueId: $this->league->id, seasonId: $this->season->id,
        fieldId: $this->field2->id, teamId: $this->team->id,
        date: '2026-04-15', startTime: '17:00', endTime: '18:00',
    );

    $result = $this->detector->check($request);
    expect($result->hasConflicts())->toBeFalse();
});

test('detects team time overlap', function () {
    ScheduleEntry::withoutEvents(fn() => ScheduleEntry::create([
        'league_id' => $this->league->id, 'season_id' => $this->season->id,
        'field_id' => $this->field->id, 'team_id' => $this->team->id,
        'date' => '2026-04-15', 'start_time' => '17:00', 'end_time' => '18:00',
        'type' => 'practice', 'status' => 'confirmed', 'created_by' => $this->user->id,
    ]));

    $request = new ScheduleRequest(
        leagueId: $this->league->id, seasonId: $this->season->id,
        fieldId: $this->field2->id, teamId: $this->team->id,
        date: '2026-04-15', startTime: '17:30', endTime: '18:30',
    );

    $result = $this->detector->check($request);
    expect($result->hasConflicts())->toBeTrue();
    expect($result->getViolations())->toHaveKey('team_overlap');
});

test('cancelled entries are ignored', function () {
    ScheduleEntry::withoutEvents(fn() => ScheduleEntry::create([
        'league_id' => $this->league->id, 'season_id' => $this->season->id,
        'field_id' => $this->field->id, 'team_id' => $this->team2->id,
        'date' => '2026-04-15', 'start_time' => '17:00', 'end_time' => '18:00',
        'type' => 'practice', 'status' => 'cancelled', 'created_by' => $this->user->id,
    ]));

    $request = new ScheduleRequest(
        leagueId: $this->league->id, seasonId: $this->season->id,
        fieldId: $this->field->id, teamId: $this->team->id,
        date: '2026-04-15', startTime: '17:00', endTime: '18:00',
    );

    $result = $this->detector->check($request);
    expect($result->hasConflicts())->toBeFalse();
});

test('detects blackout violation', function () {
    BlackoutRule::create([
        'league_id' => $this->league->id,
        'scope_type' => 'league', 'scope_id' => $this->league->id,
        'name' => 'Holiday', 'start_date' => '2026-04-15', 'end_date' => '2026-04-15',
        'recurrence' => 'none', 'is_active' => true,
    ]);

    $request = new ScheduleRequest(
        leagueId: $this->league->id, seasonId: $this->season->id,
        fieldId: $this->field->id, teamId: $this->team->id,
        date: '2026-04-15', startTime: '17:00', endTime: '18:00',
    );

    $result = $this->detector->check($request);
    expect($result->hasConflicts())->toBeTrue();
    expect($result->getViolations())->toHaveKey('blackout');
});

test('detects weekly slot limit', function () {
    $this->team->update(['max_weekly_slots' => 2]);

    // Create 2 existing entries in the same week
    ScheduleEntry::withoutEvents(function () {
        for ($i = 0; $i < 2; $i++) {
            ScheduleEntry::create([
                'league_id' => $this->league->id, 'season_id' => $this->season->id,
                'field_id' => $this->field->id, 'team_id' => $this->team->id,
                'date' => '2026-04-' . (13 + $i), 'start_time' => '17:00', 'end_time' => '18:00',
                'type' => 'practice', 'status' => 'confirmed', 'created_by' => $this->user->id,
            ]);
        }
    });

    $request = new ScheduleRequest(
        leagueId: $this->league->id, seasonId: $this->season->id,
        fieldId: $this->field2->id, teamId: $this->team->id,
        date: '2026-04-15', startTime: '17:00', endTime: '18:00',
    );

    $result = $this->detector->check($request);
    expect($result->hasConflicts())->toBeTrue();
    expect($result->getViolations())->toHaveKey('weekly_limit');
});

test('adjacent times do not conflict', function () {
    ScheduleEntry::withoutEvents(fn() => ScheduleEntry::create([
        'league_id' => $this->league->id, 'season_id' => $this->season->id,
        'field_id' => $this->field->id, 'team_id' => $this->team2->id,
        'date' => '2026-04-15', 'start_time' => '17:00', 'end_time' => '18:00',
        'type' => 'practice', 'status' => 'confirmed', 'created_by' => $this->user->id,
    ]));

    $request = new ScheduleRequest(
        leagueId: $this->league->id, seasonId: $this->season->id,
        fieldId: $this->field->id, teamId: $this->team->id,
        date: '2026-04-15', startTime: '18:00', endTime: '19:00',
    );

    $result = $this->detector->check($request);
    expect($result->hasConflicts())->toBeFalse();
});
