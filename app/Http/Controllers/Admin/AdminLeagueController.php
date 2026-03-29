<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\League;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminLeagueController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $leagues = League::withCount(['teams', 'locations', 'divisions', 'users'])
            ->with('requester:id,name,email')
            ->orderByRaw('approved_at IS NOT NULL, approved_at DESC')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Admin/Leagues', [
            'leagues' => $leagues,
        ]);
    }

    public function approve(Request $request, League $league)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $league->update(['approved_at' => now()]);

        // Auto-approve the requesting user
        if ($league->requested_by) {
            $user = User::find($league->requested_by);
            if ($user && !$user->isApproved()) {
                $user->update(['approved_at' => now()]);
            }
            // Make them league admin if not already
            if ($user && !$league->users()->where('users.id', $user->id)->exists()) {
                $league->users()->attach($user->id, ['role' => 'league_admin', 'accepted_at' => now()]);
            }
        }

        AuditLog::withoutGlobalScopes()->create([
            'league_id' => $league->id,
            'user_id' => $request->user()->id,
            'action' => 'league_approved',
            'auditable_type' => League::class,
            'auditable_id' => $league->id,
            'new_values' => ['league' => $league->name],
            'ip_address' => $request->ip(),
        ]);

        // Notify requester
        if ($league->requested_by) {
            try {
                User::find($league->requested_by)?->notify(new \App\Notifications\LeagueApproved($league));
            } catch (\Exception $e) {}
        }

        return back()->with('success', "{$league->name} has been approved.");
    }

    public function reject(Request $request, League $league)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $leagueName = $league->name;
        $requesterId = $league->requested_by;

        AuditLog::withoutGlobalScopes()->create([
            'league_id' => null,
            'user_id' => $request->user()->id,
            'action' => 'league_rejected',
            'auditable_type' => null,
            'auditable_id' => null,
            'new_values' => ['league' => $leagueName, 'requested_by' => $requesterId],
            'ip_address' => $request->ip(),
        ]);

        $league->forceDelete();

        return back()->with('success', "League request \"{$leagueName}\" has been rejected.");
    }

    public function toggleActive(Request $request, League $league)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $league->update(['is_active' => !$league->is_active]);

        $status = $league->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "{$league->name} has been {$status}.");
    }
}
