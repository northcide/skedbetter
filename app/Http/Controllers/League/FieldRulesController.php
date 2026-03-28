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
            'access_mode' => 'required|in:open,restricted',
            'rules' => 'array',
            'rules.*.division_id' => 'required|exists:divisions,id',
            'rules.*.max_weekly_slots' => 'nullable|integer|min:1',
        ]);

        if ($validated['access_mode'] === 'open') {
            // Remove all restrictions
            $field->allowedDivisions()->detach();
        } else {
            // Sync the allowed divisions with their limits
            $syncData = [];
            foreach ($validated['rules'] ?? [] as $rule) {
                $syncData[$rule['division_id']] = [
                    'max_weekly_slots' => $rule['max_weekly_slots'] ?? null,
                ];
            }
            $field->allowedDivisions()->sync($syncData);
        }

        return redirect()->route('leagues.fields.rules', [$league, $field->id])
            ->with('success', 'Field rules updated successfully.');
    }
}
