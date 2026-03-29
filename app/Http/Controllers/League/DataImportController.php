<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Field;
use App\Models\Location;
use App\Models\ScheduleEntry;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Services\LeagueContext;
use App\Services\Scheduling\ConflictDetector;
use App\Services\Scheduling\ConstraintValidator;
use App\Services\Scheduling\ScheduleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataImportController extends Controller
{
    public function create(string $league)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/DataImport/Index', [
            'league' => $context->league(),
            'userRole' => $context->userRole(),
        ]);
    }

    public function template(string $league, string $type): StreamedResponse
    {
        $templates = [
            'divisions' => [
                'headers' => ['name', 'age_group', 'skill_level'],
                'sample' => [['Majors', 'U12', 'Competitive'], ['Minors', 'U10', '']],
            ],
            'teams' => [
                'headers' => ['name', 'division', 'coach_name', 'coach_email', 'color'],
                'sample' => [['Eagles', 'Majors', 'John Smith', 'john@example.com', '#CC0000'], ['Hawks', 'Majors', 'Jane Doe', 'jane@example.com', '#000080']],
            ],
            'locations' => [
                'headers' => ['location', 'field_name', 'address', 'city', 'state'],
                'sample' => [['Central Park', 'Field A', '123 Main St', 'Springfield', 'IL'], ['Central Park', 'Field B', '', '', '']],
            ],
            'schedule' => [
                'headers' => ['team', 'field', 'date', 'start_time', 'end_time', 'type', 'title'],
                'sample' => [['Eagles', 'Field A', '2026-04-15', '17:00', '18:30', 'practice', ''], ['Hawks', 'Field B', '2026-04-15', '18:00', '19:30', 'game', 'Opener']],
            ],
        ];

        if (!isset($templates[$type])) abort(404);

        $t = $templates[$type];

        return response()->streamDownload(function () use ($t) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $t['headers']);
            foreach ($t['sample'] as $row) fputcsv($out, $row);
            fclose($out);
        }, "{$type}_template.csv", ['Content-Type' => 'text/csv']);
    }

    public function preview(Request $request, string $league)
    {
        $request->validate([
            'type' => 'required|in:divisions,teams,locations,schedule',
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $context = app(LeagueContext::class);
        $leagueModel = $context->league();
        $type = $request->type;

        $rows = $this->parseCsv($request->file('file'));
        if (empty($rows)) {
            return response()->json(['error' => 'CSV is empty or could not be parsed.'], 422);
        }

        $validated = match ($type) {
            'divisions' => $this->previewDivisions($rows, $leagueModel),
            'teams' => $this->previewTeams($rows, $leagueModel),
            'locations' => $this->previewLocations($rows, $leagueModel),
            'schedule' => $this->previewSchedule($rows, $leagueModel),
        };

        $validCount = collect($validated)->where('valid', true)->count();
        $errorCount = collect($validated)->where('valid', false)->count();

        return response()->json([
            'rows' => $validated,
            'validCount' => $validCount,
            'errorCount' => $errorCount,
            'headers' => array_keys($rows[0] ?? []),
        ]);
    }

    public function import(Request $request, string $league)
    {
        $request->validate([
            'type' => 'required|in:divisions,teams,locations,schedule',
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $context = app(LeagueContext::class);
        $leagueModel = $context->league();
        $type = $request->type;
        $user = $request->user();

        $rows = $this->parseCsv($request->file('file'));
        if (empty($rows)) {
            return response()->json(['error' => 'CSV is empty or could not be parsed.'], 422);
        }

        $result = match ($type) {
            'divisions' => $this->importDivisions($rows, $leagueModel),
            'teams' => $this->importTeams($rows, $leagueModel),
            'locations' => $this->importLocations($rows, $leagueModel),
            'schedule' => $this->importSchedule($rows, $leagueModel, $user),
        };

        return response()->json($result);
    }

    // --- CSV Parsing ---

    protected function parseCsv($file): array
    {
        $rows = [];
        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            $headers = fgetcsv($handle);
            if (!$headers) return [];
            $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) !== count($headers)) continue;
                $row = [];
                foreach ($headers as $i => $h) {
                    $row[$h] = trim($data[$i] ?? '');
                }
                $rows[] = $row;
            }
            fclose($handle);
        }
        return $rows;
    }

    // --- Divisions ---

    protected function previewDivisions(array $rows, $league): array
    {
        return array_map(function ($row) {
            $errors = [];
            if (empty($row['name'] ?? '')) $errors[] = 'Name is required';
            return ['data' => $row, 'valid' => empty($errors), 'errors' => $errors];
        }, $rows);
    }

    protected function importDivisions(array $rows, $league): array
    {
        $season = Season::where('is_current', true)->first();
        $created = 0; $skipped = 0; $errors = [];

        foreach ($rows as $i => $row) {
            if (empty($row['name'] ?? '')) { $skipped++; $errors[] = ['row' => $i + 2, 'message' => 'Name is required']; continue; }
            Division::create([
                'league_id' => $league->id,
                'season_id' => $season?->id,
                'name' => $row['name'],
                'age_group' => $row['age_group'] ?? null,
                'skill_level' => $row['skill_level'] ?? null,
            ]);
            $created++;
        }

        return compact('created', 'skipped', 'errors');
    }

    // --- Teams ---

    protected function previewTeams(array $rows, $league): array
    {
        $divisions = Division::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id]);

        return array_map(function ($row) use ($divisions) {
            $errors = [];
            if (empty($row['name'] ?? '')) $errors[] = 'Name is required';
            $divName = strtolower($row['division'] ?? '');
            if (empty($divName)) $errors[] = 'Division is required';
            elseif (!$divisions->has($divName)) $errors[] = "Division \"{$row['division']}\" not found";
            if (!empty($row['coach_email'] ?? '') && !filter_var($row['coach_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid coach email';
            return ['data' => $row, 'valid' => empty($errors), 'errors' => $errors];
        }, $rows);
    }

    protected function importTeams(array $rows, $league): array
    {
        $divisions = Division::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id]);
        $created = 0; $skipped = 0; $errors = [];

        foreach ($rows as $i => $row) {
            $divName = strtolower($row['division'] ?? '');
            if (empty($row['name'] ?? '') || !$divisions->has($divName)) {
                $skipped++;
                $errors[] = ['row' => $i + 2, 'message' => empty($row['name'] ?? '') ? 'Name required' : "Division \"{$row['division']}\" not found"];
                continue;
            }

            $team = Team::create([
                'league_id' => $league->id,
                'division_id' => $divisions[$divName],
                'name' => $row['name'],
                'contact_name' => $row['coach_name'] ?? null,
                'contact_email' => $row['coach_email'] ?? null,
                'color_code' => $row['color'] ?? null,
            ]);

            if (!empty($row['coach_email'])) {
                $this->associateCoach($team, $row['coach_email'], $row['coach_name'] ?? null, $league);
            }

            $created++;
        }

        return compact('created', 'skipped', 'errors');
    }

    // --- Locations & Fields ---

    protected function previewLocations(array $rows, $league): array
    {
        return array_map(function ($row) {
            $errors = [];
            if (empty($row['location'] ?? '')) $errors[] = 'Location name is required';
            if (empty($row['field_name'] ?? '')) $errors[] = 'Field name is required';
            return ['data' => $row, 'valid' => empty($errors), 'errors' => $errors];
        }, $rows);
    }

    protected function importLocations(array $rows, $league): array
    {
        $created = 0; $skipped = 0; $errors = [];
        $locationCache = [];

        foreach ($rows as $i => $row) {
            if (empty($row['location'] ?? '') || empty($row['field_name'] ?? '')) {
                $skipped++;
                $errors[] = ['row' => $i + 2, 'message' => 'Location and field name are required'];
                continue;
            }

            $locKey = strtolower($row['location']);
            if (!isset($locationCache[$locKey])) {
                $location = Location::where('league_id', $league->id)
                    ->whereRaw('LOWER(name) = ?', [$locKey])
                    ->first();

                if (!$location) {
                    $location = Location::create([
                        'league_id' => $league->id,
                        'name' => $row['location'],
                        'address' => $row['address'] ?? null,
                        'city' => $row['city'] ?? null,
                        'state' => $row['state'] ?? null,
                    ]);
                }
                $locationCache[$locKey] = $location;
            }

            Field::create([
                'league_id' => $league->id,
                'location_id' => $locationCache[$locKey]->id,
                'name' => $row['field_name'],
                'is_active' => true,
            ]);

            $created++;
        }

        return compact('created', 'skipped', 'errors');
    }

    // --- Schedule Entries ---

    protected function previewSchedule(array $rows, $league): array
    {
        $teams = Team::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id]);
        $fields = Field::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id]);
        $validTypes = ['practice', 'game', 'scrimmage', 'tournament', 'other'];

        return array_map(function ($row) use ($teams, $fields, $validTypes) {
            $errors = [];
            $teamName = strtolower($row['team'] ?? '');
            $fieldName = strtolower($row['field'] ?? '');

            if (empty($teamName)) $errors[] = 'Team is required';
            elseif (!$teams->has($teamName)) $errors[] = "Team \"{$row['team']}\" not found";

            if (empty($fieldName)) $errors[] = 'Field is required';
            elseif (!$fields->has($fieldName)) $errors[] = "Field \"{$row['field']}\" not found";

            if (empty($row['date'] ?? '')) $errors[] = 'Date is required';
            elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['date'])) $errors[] = 'Date must be YYYY-MM-DD';

            if (empty($row['start_time'] ?? '')) $errors[] = 'Start time is required';
            if (empty($row['end_time'] ?? '')) $errors[] = 'End time is required';

            $type = strtolower($row['type'] ?? 'practice');
            if (!in_array($type, $validTypes)) $errors[] = "Invalid type \"{$row['type']}\"";

            // Run conflict detection if basic validation passes
            if (empty($errors) && $teams->has($teamName) && $fields->has($fieldName)) {
                try {
                    $req = new ScheduleRequest(
                        teamId: $teams[$teamName],
                        fieldId: $fields[$fieldName],
                        date: $row['date'],
                        startTime: $row['start_time'],
                        endTime: $row['end_time'],
                    );
                    $conflicts = app(ConflictDetector::class)->check($req);
                    if ($conflicts->hasViolations()) {
                        foreach ($conflicts->getViolations() as $v) {
                            $errors[] = $v['message'];
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Validation error: ' . $e->getMessage();
                }
            }

            return ['data' => $row, 'valid' => empty($errors), 'errors' => $errors];
        }, $rows);
    }

    protected function importSchedule(array $rows, $league, $user): array
    {
        $teams = Team::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id]);
        $fields = Field::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id]);
        $season = Season::where('is_current', true)->first();
        $created = 0; $skipped = 0; $errors = [];

        foreach ($rows as $i => $row) {
            $teamName = strtolower($row['team'] ?? '');
            $fieldName = strtolower($row['field'] ?? '');

            if (!$teams->has($teamName) || !$fields->has($fieldName) || empty($row['date']) || empty($row['start_time']) || empty($row['end_time'])) {
                $skipped++;
                $errors[] = ['row' => $i + 2, 'message' => 'Missing required fields'];
                continue;
            }

            try {
                $req = new ScheduleRequest(
                    teamId: $teams[$teamName],
                    fieldId: $fields[$fieldName],
                    date: $row['date'],
                    startTime: $row['start_time'],
                    endTime: $row['end_time'],
                );
                $conflicts = app(ConflictDetector::class)->check($req);
                if ($conflicts->hasViolations()) {
                    $skipped++;
                    $msgs = collect($conflicts->getViolations())->pluck('message')->join('; ');
                    $errors[] = ['row' => $i + 2, 'message' => $msgs];
                    continue;
                }

                ScheduleEntry::create([
                    'league_id' => $league->id,
                    'season_id' => $season?->id,
                    'team_id' => $teams[$teamName],
                    'field_id' => $fields[$fieldName],
                    'date' => $row['date'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'type' => strtolower($row['type'] ?? 'practice') ?: 'practice',
                    'title' => $row['title'] ?? null,
                    'status' => 'confirmed',
                    'created_by' => $user->id,
                ]);
                $created++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = ['row' => $i + 2, 'message' => $e->getMessage()];
            }
        }

        return compact('created', 'skipped', 'errors');
    }

    // --- Helpers ---

    protected function associateCoach(Team $team, string $email, ?string $name, $league): void
    {
        $email = strtolower(trim($email));
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name ?: explode('@', $email)[0], 'password' => bcrypt(\Str::random(32)), 'approved_at' => now()]
        );
        if (!$user->approved_at) $user->update(['approved_at' => now()]);
        if (!$team->users()->where('users.id', $user->id)->exists()) {
            $team->users()->attach($user->id, ['role' => 'coach']);
        }
        if (!$league->users()->where('users.id', $user->id)->exists()) {
            $league->users()->attach($user->id, ['role' => 'coach', 'accepted_at' => now()]);
        }
    }
}
