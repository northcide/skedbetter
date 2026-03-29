<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Field;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FieldRulesController extends Controller
{
    public function edit(string $league, Field $field)
    {
        $context = app(LeagueContext::class);

        $field->load(['location', 'allowedDivisions']);

        $divisions = Division::with('season')
            ->orderBy('name')
            ->get();

        $fieldRules = $field->allowedDivisions->map(fn($d) => [
            'division_id' => $d->id,
            'max_weekly_slots' => $d->pivot->max_weekly_slots,
        ]);

        return Inertia::render('Leagues/Fields/Rules', [
            'league' => $context->league(),
            'field' => $field,
            'divisions' => $divisions,
            'fieldRules' => $fieldRules,
            'userRole' => $context->userRole(),
        ]);
    }

    public function update(Request $request, string $league, Field $field)
    {
        $validated = $request->validate([
            // Division access
            'access_mode' => 'required|in:open,restricted',
            'rules' => 'array',
            'rules.*.division_id' => 'required|exists:divisions,id',
            'rules.*.max_weekly_slots' => 'nullable|integer|min:1',
            // Availability
            'available_days' => 'nullable|array',
            'available_days.*' => 'integer|between:0,6',
            'available_start_time' => 'nullable|date_format:H:i',
            'available_end_time' => 'nullable|date_format:H:i',
            'slot_interval_minutes' => 'nullable|integer|in:30,60',
            'min_event_minutes' => 'nullable|integer|in:30,60,90,120',
            'max_event_minutes' => 'nullable|integer|in:30,60,90,120,180,240',
        ]);

        // Division access
        if ($validated['access_mode'] === 'open') {
            $field->allowedDivisions()->detach();
        } else {
            $syncData = [];
            foreach ($validated['rules'] ?? [] as $rule) {
                $syncData[$rule['division_id']] = [
                    'max_weekly_slots' => $rule['max_weekly_slots'] ?? null,
                ];
            }
            $field->allowedDivisions()->sync($syncData);
        }

        // Availability rules
        $field->update([
            'available_days' => ! empty($validated['available_days']) ? $validated['available_days'] : null,
            'available_start_time' => $validated['available_start_time'] ?? null,
            'available_end_time' => $validated['available_end_time'] ?? null,
            'slot_interval_minutes' => $validated['slot_interval_minutes'] ?? null,
            'min_event_minutes' => $validated['min_event_minutes'] ?? null,
            'max_event_minutes' => $validated['max_event_minutes'] ?? null,
        ]);

        return redirect()->route('leagues.fields.rules', [$league, $field->id])
            ->with('success', 'Field rules updated successfully.');
    }
}
