<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\BlackoutRule;
use App\Models\BlackoutRuleScope;
use App\Models\Location;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlackoutRuleController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);

        $rules = BlackoutRule::with('scopeEntries')
            ->orderByDesc('start_date')->get();

        // Resolve scope labels
        $locationNames = \DB::table('locations')->pluck('name', 'id');
        $fieldNames = \DB::table('fields')
            ->join('locations', 'locations.id', '=', 'fields.location_id')
            ->selectRaw("fields.id, CONCAT(fields.name, ' @ ', locations.name) as label")
            ->pluck('label', 'id');

        $rules->each(function ($rule) use ($locationNames, $fieldNames) {
            if ($rule->scope_type === 'league') {
                $rule->scope_label = 'Entire League';
            } elseif ($rule->scope_type === 'location') {
                $names = $rule->scopeEntries->map(fn($s) => $locationNames[$s->scope_id] ?? 'Unknown');
                $rule->scope_label = $names->join(', ');
            } elseif ($rule->scope_type === 'field') {
                $names = $rule->scopeEntries->map(fn($s) => $fieldNames[$s->scope_id] ?? 'Unknown');
                $rule->scope_label = $names->join(', ');
            }
        });

        return Inertia::render('Leagues/Blackouts/Index', [
            'league' => $context->league(),
            'rules' => $rules,
            'userRole' => $context->userRole(),
        ]);
    }

    public function create(string $league)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Blackouts/Create', [
            'league' => $context->league(),
            'locations' => Location::with('fields')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'reason' => 'nullable|string|max:255',
            'scope_type' => 'required|in:league,location,field',
            'scope_ids' => 'nullable|array',
            'scope_ids.*' => 'integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'recurrence' => 'required|in:none,weekly,yearly',
            'day_of_week' => 'nullable|integer|between:0,6',
        ]);

        $scopeIds = $validated['scope_ids'] ?? [];
        unset($validated['scope_ids']);
        $validated['league_id'] = $context->league()->id;

        $rule = BlackoutRule::create($validated);

        if ($validated['scope_type'] !== 'league' && !empty($scopeIds)) {
            foreach ($scopeIds as $id) {
                $rule->scopeEntries()->create([
                    'scope_type' => $validated['scope_type'],
                    'scope_id' => $id,
                ]);
            }
        }

        return redirect()->route('leagues.blackouts.index', $league)
            ->with('success', 'Blackout rule created successfully.');
    }

    public function edit(string $league, BlackoutRule $blackout)
    {
        $context = app(LeagueContext::class);
        $blackout->load('scopeEntries');

        return Inertia::render('Leagues/Blackouts/Edit', [
            'league' => $context->league(),
            'rule' => $blackout,
            'scopeIds' => $blackout->scopeEntries->pluck('scope_id')->toArray(),
            'locations' => Location::with('fields')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $league, BlackoutRule $blackout)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'reason' => 'nullable|string|max:255',
            'scope_type' => 'required|in:league,location,field',
            'scope_ids' => 'nullable|array',
            'scope_ids.*' => 'integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'recurrence' => 'required|in:none,weekly,yearly',
            'day_of_week' => 'nullable|integer|between:0,6',
            'is_active' => 'boolean',
        ]);

        $scopeIds = $validated['scope_ids'] ?? [];
        unset($validated['scope_ids']);

        $blackout->update($validated);

        // Sync scope entries
        $blackout->scopeEntries()->delete();
        if ($validated['scope_type'] !== 'league' && !empty($scopeIds)) {
            foreach ($scopeIds as $id) {
                $blackout->scopeEntries()->create([
                    'scope_type' => $validated['scope_type'],
                    'scope_id' => $id,
                ]);
            }
        }

        return redirect()->route('leagues.blackouts.index', $league)
            ->with('success', 'Blackout rule updated successfully.');
    }

    public function destroy(string $league, BlackoutRule $blackout)
    {
        $blackout->forceDelete();

        return redirect()->route('leagues.blackouts.index', $league)
            ->with('success', 'Blackout rule deleted.');
    }
}
