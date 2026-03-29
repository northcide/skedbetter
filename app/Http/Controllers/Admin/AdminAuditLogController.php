<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $query = AuditLog::withoutGlobalScopes()
            ->whereNull('league_id')
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(50);

        $actions = AuditLog::withoutGlobalScopes()
            ->whereNull('league_id')
            ->select('action')
            ->distinct()
            ->pluck('action');

        return Inertia::render('Admin/AuditLog', [
            'logs' => $logs,
            'actions' => $actions,
            'filters' => $request->only(['action']),
        ]);
    }
}
