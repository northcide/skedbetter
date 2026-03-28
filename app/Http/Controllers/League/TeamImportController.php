<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Team;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamImportController extends Controller
{
    public function create(string $league)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Teams/Import', [
            'league' => $context->league(),
            'divisions' => Division::with('season')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $request->validate([
            'division_id' => 'required|exists:divisions,id',
            'csv_file' => 'required|file|mimes:csv,txt|max:1024',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return back()->with('error', 'Could not read the CSV file.');
        }

        // Read header row
        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            return back()->with('error', 'CSV file is empty.');
        }

        // Normalize headers
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $nameIdx = array_search('name', $header);
        $contactNameIdx = array_search('contact_name', $header);
        $contactEmailIdx = array_search('contact_email', $header);
        $contactPhoneIdx = array_search('contact_phone', $header);
        $colorIdx = array_search('color', $header);

        if ($nameIdx === false) {
            fclose($handle);
            return back()->with('error', 'CSV must have a "name" column.');
        }

        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim($row[$nameIdx] ?? '');
            if (empty($name)) {
                $skipped++;
                continue;
            }

            Team::create([
                'division_id' => $request->division_id,
                'league_id' => $context->league()->id,
                'name' => $name,
                'contact_name' => $contactNameIdx !== false ? trim($row[$contactNameIdx] ?? '') : null,
                'contact_email' => $contactEmailIdx !== false ? trim($row[$contactEmailIdx] ?? '') : null,
                'contact_phone' => $contactPhoneIdx !== false ? trim($row[$contactPhoneIdx] ?? '') : null,
                'color_code' => $colorIdx !== false ? trim($row[$colorIdx] ?? '') : null,
            ]);

            $created++;
        }

        fclose($handle);

        $message = "Imported {$created} team(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} row(s) skipped (empty name).";
        }

        return redirect()->route('leagues.teams.index', $league)
            ->with('success', $message);
    }
}
