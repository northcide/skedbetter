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
            'userRole' => $context->userRole(),
        ]);
    }

    public function store(Request $request, string $league)
    {
        $context = app(LeagueContext::class);

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:league_manager,division_manager,team_manager,coach',
        ]);

        // Check if already a member
        $existing = $context->league()->users()
            ->where('email', $validated['email'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['email' => 'This user is already a member of this league.']);
        }

        // Check for pending invitation
        $pendingInvite = LeagueInvitation::where('league_id', $context->league()->id)
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($pendingInvite) {
            return back()->withErrors(['email' => 'A pending invitation already exists for this email.']);
        }

        $invitation = LeagueInvitation::create([
            'league_id' => $context->league()->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'invited_by' => $request->user()->id,
        ]);

        // Send email
        Notification::route('mail', $validated['email'])
            ->notify(new LeagueInvitationNotification($invitation));

        return back()->with('success', "Invitation sent to {$validated['email']}.");
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
