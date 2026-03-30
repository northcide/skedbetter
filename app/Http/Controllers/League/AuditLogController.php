<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Field;
use App\Models\Team;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request, string $league)
    {
        $context = app(LeagueContext::class);
        $role = $context->userRole();

        if (! in_array($role, ['superadmin', 'league_admin'])) {
            abort(403);
        }

        $leagueId = $context->league()->id;
        $leagueMemberIds = \DB::table('league_user')->where('league_id', $leagueId)->pluck('user_id');

        $query = AuditLog::withoutGlobalScopes()
            ->where(function ($q) use ($leagueId, $leagueMemberIds) {
                $q->where('league_id', $leagueId)
                  ->orWhere(function ($q2) use ($leagueMemberIds) {
                      $q2->whereNull('league_id')
                         ->whereIn('user_id', $leagueMemberIds);
                  });
            })
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate(50);

        // Get unique users and actions for filters
        $users = AuditLog::withoutGlobalScopes()
            ->where(function ($q) use ($leagueId, $leagueMemberIds) {
                $q->where('league_id', $leagueId)
                  ->orWhere(function ($q2) use ($leagueMemberIds) {
                      $q2->whereNull('league_id')
                         ->whereIn('audit_logs.user_id', $leagueMemberIds);
                  });
            })
            ->join('users', 'users.id', '=', 'audit_logs.user_id')
            ->select('users.id', 'users.name')
            ->distinct()
            ->get();

        // Build lookup maps for human-readable names
        $teamNames = Team::withoutGlobalScopes()->where('league_id', $leagueId)->pluck('name', 'id');
        $fieldNames = Field::withoutGlobalScopes()->where('league_id', $leagueId)->pluck('name', 'id');

        // Enrich audit log values with readable names
        $logs->through(function ($log) use ($teamNames, $fieldNames) {
            $log->old_values = $this->enrichValues($log->old_values, $teamNames, $fieldNames);
            $log->new_values = $this->enrichValues($log->new_values, $teamNames, $fieldNames);

            return $log;
        });

        return Inertia::render('Leagues/AuditLog/Index', [
            'league' => $context->league(),
            'logs' => $logs,
            'users' => $users,
            'filters' => $request->only(['action', 'user_id']),
            'userRole' => $role,
        ]);
    }

    protected function enrichValues(?array $values, $teamNames, $fieldNames): ?array
    {
        if (! $values) {
            return $values;
        }

        $labelMap = [
            'field_id' => 'Field',
            'team_id' => 'Team',
            'date' => 'Date',
            'start_time' => 'Start',
            'end_time' => 'End',
            'status' => 'Status',
            'type' => 'Type',
            'title' => 'Title',
            'notes' => 'Notes',
            'method' => 'Method',
            'reason' => 'Reason',
        ];

        $enriched = [];

        foreach ($values as $key => $val) {
            // Skip internal fields
            if (in_array($key, ['updated_at', 'created_at', 'created_by', 'updated_by', 'league_id', 'id'])) {
                continue;
            }

            $label = $labelMap[$key] ?? $key;

            if ($key === 'field_id') {
                $enriched[$label] = $fieldNames[$val] ?? "Field #{$val}";
            } elseif ($key === 'team_id') {
                $enriched[$label] = $teamNames[$val] ?? "Team #{$val}";
            } elseif (in_array($key, ['start_time', 'end_time']) && $val) {
                $enriched[$label] = \Carbon\Carbon::parse($val)->format('g:i A');
            } elseif ($key === 'date' && $val) {
                $enriched[$label] = \Carbon\Carbon::parse($val)->format('M j, Y');
            } else {
                $enriched[$label] = $val;
            }
        }

        return $enriched;
    }
}
