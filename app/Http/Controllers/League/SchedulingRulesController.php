<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Field;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SchedulingRulesController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);

        $divisions = Division::withCount('teams')->with('season')->orderBy('sort_order')->orderBy('name')->get();
        $fields = Field::with('location')->where('is_active', true)->orderBy('name')->get();

        // Group fields by location
        $fieldsByLocation = [];
        foreach ($fields as $field) {
            $locName = $field->location->name ?? 'No Location';
            $locId = $field->location_id ?? 0;
            if (!isset($fieldsByLocation[$locId])) {
                $fieldsByLocation[$locId] = [
                    'name' => $locName,
                    'fields' => [],
                ];
            }
            $fieldsByLocation[$locId]['fields'][] = $field;
        }

        // Get all division_field pivot data
        $pivots = DB::table('division_field')
            ->get()
            ->groupBy('field_id');

        // Build matrix: field_id => division_id => pivot data
        $matrix = [];
        foreach ($fields as $field) {
            $fieldPivots = $pivots->get($field->id, collect());
            $matrix[$field->id] = [];
            foreach ($fieldPivots as $p) {
                $matrix[$field->id][$p->division_id] = [
                    'max_weekly_slots' => $p->max_weekly_slots,
                    'priority' => $p->priority,
                    'booking_window_type' => $p->booking_window_type,
                    'booking_opens_date' => $p->booking_opens_date,
                    'booking_opens_days' => $p->booking_opens_days,
                ];
            }
        }

        return Inertia::render('Leagues/SchedulingRules/Index', [
            'league' => $context->league(),
            'divisions' => $divisions,
            'fields' => $fields,
            'fieldsByLocation' => array_values($fieldsByLocation),
            'matrix' => $matrix,
            'userRole' => $context->userRole(),
        ]);
    }

    public function update(Request $request, string $league)
    {
        $validated = $request->validate([
            'rules' => 'required|array',
            'rules.*.field_id' => 'required|exists:fields,id',
            'rules.*.division_id' => 'required|exists:divisions,id',
            'rules.*.enabled' => 'required|boolean',
            'rules.*.max_weekly_slots' => 'nullable|integer|min:1',
            'rules.*.priority' => 'nullable|integer|min:1|max:5',
            'rules.*.booking_window_type' => 'nullable|in:calendar,rolling',
            'rules.*.booking_opens_date' => 'nullable|date',
            'rules.*.booking_opens_days' => 'nullable|integer|min:1',
        ]);

        foreach ($validated['rules'] as $rule) {
            if ($rule['enabled']) {
                DB::table('division_field')->updateOrInsert(
                    ['field_id' => $rule['field_id'], 'division_id' => $rule['division_id']],
                    [
                        'max_weekly_slots' => $rule['max_weekly_slots'] ?? null,
                        'priority' => $rule['priority'] ?? null,
                        'booking_window_type' => $rule['booking_window_type'] ?? null,
                        'booking_opens_date' => ($rule['booking_window_type'] ?? null) === 'calendar' ? ($rule['booking_opens_date'] ?? null) : null,
                        'booking_opens_days' => ($rule['booking_window_type'] ?? null) === 'rolling' ? ($rule['booking_opens_days'] ?? null) : null,
                        'updated_at' => now(),
                    ]
                );
            } else {
                DB::table('division_field')
                    ->where('field_id', $rule['field_id'])
                    ->where('division_id', $rule['division_id'])
                    ->delete();
            }
        }

        return back()->with('success', 'Scheduling rules saved.');
    }
}
