<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\League;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeagueController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isSuperadmin()) {
            $leagues = League::whereNotNull('approved_at')
                ->withCount(['teams', 'locations', 'divisions', 'fields'])->latest()->get();
            $leagues->each(fn ($l) => $l->user_role = 'superadmin');
        } else {
            $leagues = $user->leagues()
                ->whereNotNull('leagues.approved_at')
                ->withCount(['teams', 'locations', 'divisions', 'fields'])
                ->latest()
                ->get();
            $leagues->each(fn ($l) => $l->user_role = $l->pivot->role ?? 'coach');
        }

        // Expose public calendar URL for managers
        $leagues->each(function ($l) {
            if (in_array($l->user_role, ['superadmin', 'league_admin', 'division_manager']) && $l->public_token) {
                $l->public_calendar_url = url('/p/' . $l->public_token);
            }
        });

        // Pending league requests by this user
        $pendingLeagues = League::whereNull('approved_at')
            ->where('requested_by', $user->id)
            ->latest()
            ->get(['id', 'name', 'created_at']);

        return Inertia::render('Leagues/Index', [
            'leagues' => $leagues,
            'pendingLeagues' => $pendingLeagues,
            'canCreateLeague' => $user->isSuperadmin() || $this->isExistingLeagueAdmin($user),
            'isSuperadmin' => $user->isSuperadmin(),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        if (!$user->isSuperadmin() && !$this->isExistingLeagueAdmin($user)) {
            abort(403);
        }

        return Inertia::render('Leagues/Create', [
            'isSuperadmin' => $user->isSuperadmin(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->isSuperadmin() && !$this->isExistingLeagueAdmin($user)) {
            abort(403);
        }

        if ($user->isSuperadmin()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'timezone' => 'required|string|timezone',
                'admin_email' => 'required|email',
            ]);

            $adminEmail = $validated['admin_email'];
            unset($validated['admin_email']);
            $validated['contact_email'] = $adminEmail;
            $validated['approved_at'] = now();

            $league = League::create($validated);

            session(["league_{$league->id}_admin_email" => $adminEmail]);

            return redirect()->route('leagues.onboarding', $league->slug)
                ->with('success', 'League created! Now set it up.');
        }

        // League admin creating a new league — auto-approved, they are the admin
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'timezone' => 'required|string|timezone',
        ]);

        $validated['contact_email'] = $user->email;
        $validated['requested_by'] = $user->id;
        $validated['approved_at'] = now();

        $league = League::create($validated);

        // Make them league admin
        $league->users()->attach($user->id, ['role' => 'league_admin', 'accepted_at' => now()]);

        return redirect()->route('leagues.onboarding', $league->slug)
            ->with('success', 'League created! Now set it up.');
    }

    public function show(Request $request, string $league)
    {
        $league = League::where('slug', $league)
            ->withCount(['teams', 'locations', 'divisions', 'fields'])
            ->firstOrFail();

        $user = $request->user();
        $this->authorizeLeagueAccess($user, $league);

        $currentSeason = $league->currentSeason;

        return Inertia::render('Leagues/Show', [
            'league' => $league,
            'currentSeason' => $currentSeason,
            'userRole' => $this->getUserLeagueRole($user, $league),
        ]);
    }

    public function edit(Request $request, string $league)
    {
        $league = League::where('slug', $league)->firstOrFail();
        $user = $request->user();
        $this->authorizeLeagueManager($user, $league);

        return Inertia::render('Leagues/Edit', [
            'league' => $league,
        ]);
    }

    public function update(Request $request, string $league)
    {
        $league = League::where('slug', $league)->firstOrFail();
        $this->authorizeLeagueManager($request->user(), $league);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'timezone' => 'required|string|timezone',
            'contact_email' => 'nullable|email',
        ]);

        $league->update($validated);

        return redirect()->route('leagues.show', $league->slug)
            ->with('success', 'League updated successfully.');
    }

    public function destroy(Request $request, string $league)
    {
        $this->authorizeSuperadmin();

        $league = League::where('slug', $league)->firstOrFail();

        if ($league->is_active) {
            return back()->with('error', 'Deactivate the league before deleting it.');
        }

        $league->forceDelete();

        return redirect()->route('leagues.index')
            ->with('success', 'League permanently deleted.');
    }

    public function toggleActive(Request $request, string $league)
    {
        $this->authorizeSuperadmin();

        $league = League::where('slug', $league)->firstOrFail();
        $league->update(['is_active' => ! $league->is_active]);

        $status = $league->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "League \"{$league->name}\" has been {$status}.");
    }

    public function generatePublicToken(Request $request, string $league)
    {
        $league = League::where('slug', $league)->firstOrFail();
        $this->authorizeLeagueManager($request->user(), $league);

        $league->generatePublicToken();

        return back()->with('success', 'Public calendar link generated.');
    }

    public function revokePublicToken(Request $request, string $league)
    {
        $league = League::where('slug', $league)->firstOrFail();
        $this->authorizeLeagueManager($request->user(), $league);

        $league->revokePublicToken();
        $league->generatePublicToken();

        return back()->with('success', 'Old link expired. A new public link has been generated.');
    }

    protected function authorizeSuperadmin(): void
    {
        if (! request()->user()->isSuperadmin()) {
            abort(403);
        }
    }

    protected function isExistingLeagueAdmin($user): bool
    {
        return $user->leagues()->wherePivot('role', 'league_admin')->exists();
    }

    protected function authorizeLeagueAccess($user, League $league): void
    {
        if ($user->isSuperadmin()) {
            return;
        }

        if (! $user->leagues()->where('leagues.id', $league->id)->exists()) {
            abort(403);
        }
    }

    protected function authorizeLeagueManager($user, League $league): void
    {
        if ($user->isSuperadmin()) {
            return;
        }

        $membership = $user->leagues()
            ->where('leagues.id', $league->id)
            ->wherePivotIn('role', ['league_admin', 'division_manager'])
            ->exists();

        if (! $membership) {
            abort(403);
        }
    }

    protected function getUserLeagueRole($user, League $league): string
    {
        if ($user->isSuperadmin()) {
            return 'superadmin';
        }

        return $user->leagues()
            ->where('leagues.id', $league->id)
            ->first()
            ?->pivot
            ?->role ?? 'none';
    }
}
