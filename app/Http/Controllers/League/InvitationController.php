<?php

namespace App\Http\Controllers\League;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Mail\MagicLinkMail;
use App\Models\LeagueInvitation;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\LeagueInvitation as LeagueInvitationNotification;
use App\Services\LeagueContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function index(string $league)
    {
        $context = app(LeagueContext::class);

        $invitations = LeagueInvitation::where('league_id', $context->league()->id)
            ->with('inviter')
            ->orderByDesc('created_at')
            ->get();

        $members = $context->league()->users()
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->pivot->role,
                'accepted_at' => $u->pivot->accepted_at,
            ]);

        return Inertia::render('Leagues/Members/Index', [
            'league' => $context->league(),
            'members' => $members,
            'invitations' => $invitations,
            'divisions' => \App\Models\Division::with(['teams' => fn($q) => $q->orderBy('name')])->orderBy('name')->get(),
            'teams' => \App\Models\Team::with('division')->orderBy('name')->get(),
            'userRole' => $context->userRole(),
        ]);
    }

    public function store(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'role' => 'required|in:league_admin,division_manager,coach',
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
        ]);

        $email = strtolower(trim($validated['email']));

        // Coaches need at least one team
        if ($validated['role'] === 'coach' && empty($validated['team_ids'])) {
            return back()->withErrors(['team_ids' => 'Please select at least one team for the coach.']);
        }

        // Create or find user
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $validated['name'] ?: explode('@', $email)[0], 'password' => bcrypt(\Str::random(32))]
        );

        if (! empty($validated['name']) && ! $user->wasRecentlyCreated && $user->name === explode('@', $email)[0]) {
            $user->update(['name' => $validated['name']]);
        }

        // Add to league immediately
        $context->league()->users()->syncWithoutDetaching([
            $user->id => ['role' => $validated['role'], 'accepted_at' => now()],
        ]);

        // Division manager: assign to selected divisions
        if ($validated['role'] === 'division_manager' && ! empty($validated['division_ids'])) {
            $user->managedDivisions()->syncWithoutDetaching($validated['division_ids']);
        }

        // Coach: assign to selected teams
        if ($validated['role'] === 'coach' && ! empty($validated['team_ids'])) {
            foreach ($validated['team_ids'] as $teamId) {
                $team = \App\Models\Team::find($teamId);
                if ($team && ! $team->users()->where('users.id', $user->id)->exists()) {
                    $team->users()->attach($user->id, ['role' => 'coach']);
                }
            }
        }

        return back()->with('success', "{$user->name} added as " . str_replace('_', ' ', $validated['role']) . ".");
    }

    public function destroy(string $league, LeagueInvitation $invitation)
    {
        $invitation->delete();

        return back()->with('success', 'Invitation revoked.');
    }

    public function removeMember(Request $request, string $league, User $user)
    {
        $context = app(LeagueContext::class);

        // Prevent removing yourself
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot remove yourself from the league.');
        }

        $context->league()->users()->detach($user->id);

        return back()->with('success', "{$user->name} removed from the league.");
    }

    public function sendMagicLink(Request $request, string $league, User $user)
    {
        $magicLink = MagicLinkController::generateForUser($user->email, $request->user()->id);

        try {
            Mail::to($user->email)->send(new MagicLinkMail($magicLink));
            return back()->with('success', "Login link sent to {$user->email}.");
        } catch (\Exception $e) {
            return back()->with('error', "Failed to send email: {$e->getMessage()}");
        }
    }

    public function generateMagicLink(Request $request, string $league, User $user)
    {
        $magicLink = MagicLinkController::generateForUser($user->email, $request->user()->id);

        return response()->json(['url' => $magicLink->getUrl()]);
    }
}
