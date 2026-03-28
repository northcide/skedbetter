<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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

        $query = AuditLog::where('league_id', $context->league()->id)
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
        $users = AuditLog::where('league_id', $context->league()->id)
            ->join('users', 'users.id', '=', 'audit_logs.user_id')
            ->select('users.id', 'users.name')
            ->distinct()
            ->get();

        return Inertia::render('Leagues/AuditLog/Index', [
            'league' => $context->league(),
            'logs' => $logs,
            'users' => $users,
            'filters' => $request->only(['action', 'user_id']),
            'userRole' => $role,
        ]);
    }
}
