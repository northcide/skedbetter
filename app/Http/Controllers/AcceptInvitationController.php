<?php

namespace App\Http\Controllers;

use App\Models\LeagueInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcceptInvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = LeagueInvitation::where('token', $token)
            ->with('league')
            ->firstOrFail();

        if ($invitation->isAccepted()) {
            return Inertia::render('Invitations/AlreadyAccepted', [
                'league' => $invitation->league,
            ]);
        }

        if ($invitation->isExpired()) {
            return Inertia::render('Invitations/Expired', [
                'league' => $invitation->league,
            ]);
        }

        return Inertia::render('Invitations/Accept', [
            'invitation' => $invitation,
            'league' => $invitation->league,
            'isLoggedIn' => auth()->check(),
            'emailMatch' => auth()->check() && auth()->user()->email === $invitation->email,
        ]);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = LeagueInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isAccepted() || $invitation->isExpired()) {
            return redirect()->route('leagues.index');
        }

        $user = $request->user();
        if (! $user) {
            // Store token in session and redirect to register
            session(['invitation_token' => $token]);
            return redirect()->route('register');
        }

        // Add user to league
        $invitation->league->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $invitation->role,
                'invited_at' => $invitation->created_at,
                'accepted_at' => now(),
            ],
        ]);

        $invitation->update(['accepted_at' => now()]);

        return redirect()->route('leagues.show', $invitation->league->slug)
            ->with('success', "Welcome to {$invitation->league->name}!");
    }
}
