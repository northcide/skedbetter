<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserApprovalController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $pending = User::whereNull('approved_at')
            ->where('is_superadmin', false)
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'created_at']);

        $recentlyApproved = User::whereNotNull('approved_at')
            ->where('is_superadmin', false)
            ->orderByDesc('approved_at')
            ->limit(20)
            ->get(['id', 'name', 'email', 'approved_at', 'created_at']);

        return Inertia::render('Admin/UserApprovals', [
            'pending' => $pending,
            'recentlyApproved' => $recentlyApproved,
        ]);
    }

    public function approve(Request $request, User $user)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $user->update(['approved_at' => now()]);

        // Notify the user
        try {
            $user->notify(new \App\Notifications\AccountApproved());
        } catch (\Exception $e) {
            // Don't fail if notification can't be sent
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', "{$user->name} has been approved.");
    }

    public function reject(Request $request, User $user)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $user->forceDelete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', "Registration for {$user->name} has been rejected.");
    }
}
