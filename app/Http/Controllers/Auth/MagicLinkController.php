<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class MagicLinkController extends Controller
{
    public function request(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Don't reveal whether the user exists
            return back()->with('success', 'If an account exists with that email, a login link has been sent.');
        }

        // Invalidate existing unused links for this email
        MagicLink::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        $magicLink = MagicLink::generate($email);

        try {
            Mail::to($email)->send(new MagicLinkMail($magicLink));
        } catch (\Exception $e) {
            return back()->with('error', 'Unable to send email. Please contact your league manager.');
        }

        return back()->with('success', 'If an account exists with that email, a login link has been sent.');
    }

    public function verify(string $token)
    {
        $magicLink = MagicLink::where('token', $token)->first();

        if (! $magicLink) {
            return Inertia::render('Auth/MagicLinkExpired', ['reason' => 'invalid']);
        }

        if (! $magicLink->isValid()) {
            return Inertia::render('Auth/MagicLinkExpired', ['reason' => 'expired']);
        }

        $user = User::where('email', $magicLink->email)->first();

        if (! $user) {
            return Inertia::render('Auth/MagicLinkExpired', ['reason' => 'no_account']);
        }

        $magicLink->markUsed();

        // Mark email as verified if not already
        if (! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        $user->update(['last_login_at' => now()]);

        Auth::login($user, remember: true);

        return redirect()->route('leagues.index');
    }

    // Generate a magic link for a specific user (called by managers)
    public static function generateForUser(string $email, ?int $createdBy = null): MagicLink
    {
        $email = strtolower(trim($email));

        // Invalidate existing
        MagicLink::where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        return MagicLink::generate($email, $createdBy);
    }
}
