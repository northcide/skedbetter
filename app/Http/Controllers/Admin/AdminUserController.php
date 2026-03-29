<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $users = User::with(['leagues:id,name'])
            ->where('is_superadmin', false)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'email_verified_at' => $u->email_verified_at,
                'approved_at' => $u->approved_at,
                'last_login_at' => $u->last_login_at,
                'created_at' => $u->created_at,
                'leagues' => $u->leagues->map(fn ($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'role' => $l->pivot->role,
                ]),
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    public function toggleActive(Request $request, User $user)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        if ($user->approved_at) {
            $user->update(['approved_at' => null]);
            $status = 'deactivated';
        } else {
            $user->update(['approved_at' => now()]);
            $status = 'reactivated';
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $status]);
        }

        return back()->with('success', "{$user->name} has been {$status}.");
    }
}
