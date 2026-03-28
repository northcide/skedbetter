<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\ScheduleEntry;
use Illuminate\Http\Request;

class IcalController extends Controller
{
    public function team(Request $request, int $teamId)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }

        $team = Team::withoutGlobalScopes()->findOrFail($teamId);
        $entries = ScheduleEntry::withoutGlobalScopes()
            ->where('team_id', $team->id)
            ->where('status', '!=', 'cancelled')
            ->with(['field.location'])
            ->orderBy('date')
            ->get();

        $league = $team->league;
        $tz = $league?->timezone ?? 'America/Chicago';
        $calName = "{$team->name} Schedule";

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//SkedBetter//Schedule//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:{$calName}\r\n";
        $ical .= "X-WR-TIMEZONE:{$tz}\r\n";

        foreach ($entries as $entry) {
            $dtStart = $entry->date->format('Ymd') . 'T' . str_replace(':', '', $entry->start_time);
            $dtEnd = $entry->date->format('Ymd') . 'T' . str_replace(':', '', $entry->end_time);
            $summary = $entry->title ?: $team->name . ' - ' . ucfirst($entry->type->value ?? $entry->type);
            $location = '';
            if ($entry->field) {
                $location = $entry->field->name;
                if ($entry->field->location) {
                    $location .= ', ' . $entry->field->location->name;
                    if ($entry->field->location->address) {
                        $location .= ', ' . $entry->field->location->address;
                    }
                }
            }
            $uid = "skedbetter-{$entry->id}@skedbetter";
            $stamp = now()->format('Ymd\THis\Z');

            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:{$uid}\r\n";
            $ical .= "DTSTAMP:{$stamp}\r\n";
            $ical .= "DTSTART;TZID={$tz}:{$dtStart}\r\n";
            $ical .= "DTEND;TZID={$tz}:{$dtEnd}\r\n";
            $ical .= "SUMMARY:" . $this->escapeIcal($summary) . "\r\n";
            if ($location) {
                $ical .= "LOCATION:" . $this->escapeIcal($location) . "\r\n";
            }
            if ($entry->notes) {
                $ical .= "DESCRIPTION:" . $this->escapeIcal($entry->notes) . "\r\n";
            }
            $ical .= "STATUS:" . ($entry->status->value === 'tentative' ? 'TENTATIVE' : 'CONFIRMED') . "\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        return response($ical, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="' . str_replace(' ', '_', $team->name) . '.ics"',
        ]);
    }

    protected function escapeIcal(string $text): string
    {
        return str_replace(
            ["\r\n", "\n", "\r", ',', ';', '\\'],
            ['\\n', '\\n', '\\n', '\\,', '\\;', '\\\\'],
            $text
        );
    }
}
