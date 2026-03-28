<?php

namespace App\Http\Controllers;

use App\Models\League;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeagueController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isSuperadmin()) {
            $leagues = League::withCount(['teams', 'locations', 'divisions'])->latest()->get();
        } else {
            $leagues = $user->leagues()
                ->withCount(['teams', 'locations', 'divisions'])
                ->latest()
                ->get();
        }

        return Inertia::render('Leagues/Index', [
            'leagues' => $leagues,
            'canCreateLeague' => $user->isSuperadmin(),
            'isSuperadmin' => $user->isSuperadmin(),
        ]);
    }

    public function create()
    {
        $this->authorizeSuperadmin();

        return Inertia::render('Leagues/Create');
    }

    public function store(Request $request)
    {
        $this->authorizeSuperadmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'timezone' => 'required|string|timezone',
            'admin_email' => 'required|email',
        ]);

        $adminEmail = $validated['admin_email'];
        unset($validated['admin_email']);
        $validated['contact_email'] = $adminEmail;

        $league = League::create($validated);

        // Store admin email in session for the wizard to use
        session(["league_{$league->id}_admin_email" => $adminEmail]);

        return redirect()->route('leagues.onboarding', $league->slug)
            ->with('success', 'League created! Now set it up.');
    }

    public function show(Request $request, string $league)
    {
        $league = League::where('slug', $league)
            ->withCount(['teams', 'locations', 'divisions'])
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
        $league->delete();

        return redirect()->route('leagues.index')
            ->with('success', 'League deleted successfully.');
    }

    protected function authorizeSuperadmin(): void
    {
        if (! request()->user()->isSuperadmin()) {
            abort(403);
        }
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
