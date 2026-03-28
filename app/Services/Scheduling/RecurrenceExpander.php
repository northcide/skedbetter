<?php

namespace App\Services\Scheduling;

use Carbon\Carbon;

class RecurrenceExpander
{
    /**
     * Expand a recurrence pattern into individual dates.
     *
     * @param array $pattern ['frequency' => 'weekly', 'day' => 2, 'until' => '2026-06-15']
     * @param string $startDate First occurrence date
     * @return array<string> Array of date strings (Y-m-d)
     */
    public function expand(array $pattern, string $startDate): array
    {
        $dates = [];
        $frequency = $pattern['frequency'] ?? 'weekly';
        $until = Carbon::parse($pattern['until']);
        $current = Carbon::parse($startDate);

        // Cap at 52 weeks to prevent runaway expansion
        $maxIterations = 52;
        $i = 0;

        while ($current->lte($until) && $i < $maxIterations) {
            $dates[] = $current->toDateString();
            $current = match ($frequency) {
                'weekly' => $current->copy()->addWeek(),
                'biweekly' => $current->copy()->addWeeks(2),
                'daily' => $current->copy()->addDay(),
                default => $current->copy()->addWeek(),
            };
            $i++;
        }

        return $dates;
    }
}
