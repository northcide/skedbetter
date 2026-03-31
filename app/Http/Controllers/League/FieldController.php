<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\FieldType;
use App\Models\Location;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FieldController extends Controller
{
    public function create(string $league, Location $location)
    {
        $context = app(LeagueContext::class);

        return Inertia::render('Leagues/Fields/Create', [
            'league' => $context->league(),
            'location' => $location,
            'fieldTypes' => FieldType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, string $league, Location $location)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'field_type_id' => 'nullable|exists:field_types,id',
            'capacity' => 'nullable|integer|min:0',
            'is_lighted' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['location_id'] = $location->id;
        $validated['league_id'] = $context->league()->id;

        Field::create($validated);

        return redirect()->route('leagues.locations.edit', [$league, $location])
            ->with('success', 'Field created successfully.');
    }

    public function edit(string $league, Field $field)
    {
        $context = app(LeagueContext::class);

        $field->load(['location', 'allowedDivisions', 'timeSlots']);

        $fieldRules = $field->allowedDivisions->map(fn($d) => [
            'division_id' => $d->id,
            'max_weekly_slots' => $d->pivot->max_weekly_slots,
        ]);

        return Inertia::render('Leagues/Fields/Edit', [
            'league' => $context->league(),
            'field' => $field,
            'fieldTypes' => FieldType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'divisions' => \App\Models\Division::with('season')->orderBy('name')->get(),
            'fieldRules' => $fieldRules,
            'timeSlots' => $field->timeSlots,
            'userRole' => $context->userRole(),
        ]);
    }

    public function update(Request $request, string $league, Field $field)
    {
        $validated = $request->validate([
            // Field details
            'name' => 'required|string|max:255',
            'field_type_id' => 'nullable|exists:field_types,id',
            'capacity' => 'nullable|integer|min:0',
            'is_lighted' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            // Availability
            'available_days' => 'nullable|array',
            'available_days.*' => 'integer|between:0,6',
            'available_start_time' => 'nullable|date_format:H:i',
            'available_end_time' => 'nullable|date_format:H:i',
            'slot_interval_minutes' => 'nullable|integer|in:30,60',
            'min_event_minutes' => 'nullable|integer|in:30,60,90,120',
            'max_event_minutes' => 'nullable|integer|in:30,60,90,120,180,240',
            // Division access
            'access_mode' => 'nullable|in:open,restricted',
            'rules' => 'nullable|array',
            'rules.*.division_id' => 'required|exists:divisions,id',
            'rules.*.max_weekly_slots' => 'nullable|integer|min:1',
            // Time slots
            'time_slots' => 'nullable|array',
            'time_slots.*.day_of_week' => 'required|integer|between:0,6',
            'time_slots.*.start_time' => 'required|date_format:H:i',
            'time_slots.*.end_time' => 'required|date_format:H:i|after:time_slots.*.start_time',
            'time_slots.*.label' => 'nullable|string|max:100',
        ]);

        // Field details + availability
        $field->update([
            'name' => $validated['name'],
            'field_type_id' => $validated['field_type_id'] ?? null,
            'capacity' => $validated['capacity'] ?? null,
            'is_lighted' => $validated['is_lighted'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
            'available_days' => ! empty($validated['available_days']) ? $validated['available_days'] : null,
            'available_start_time' => $validated['available_start_time'] ?? null,
            'available_end_time' => $validated['available_end_time'] ?? null,
            'slot_interval_minutes' => $validated['slot_interval_minutes'] ?? null,
            'min_event_minutes' => $validated['min_event_minutes'] ?? null,
            'max_event_minutes' => $validated['max_event_minutes'] ?? null,
        ]);

        // Division access
        if (isset($validated['access_mode'])) {
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
        }

        // Time slots
        if (isset($validated['time_slots'])) {
            $field->timeSlots()->delete();
            $order = 0;
            foreach ($validated['time_slots'] as $slot) {
                $field->timeSlots()->create([
                    'day_of_week' => $slot['day_of_week'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'label' => $slot['label'] ?? null,
                    'sort_order' => $order++,
                ]);
            }
        }

        return redirect()->route('leagues.fields.edit', [$league, $field->id])
            ->with('success', 'Field saved.');
    }

    public function destroy(string $league, Field $field)
    {
        $locationId = $field->location_id;
        $field->delete();

        return redirect()->route('leagues.locations.edit', [$league, $locationId])
            ->with('success', 'Field deleted successfully.');
    }
}
