<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\AuditLog;
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
            return redirect()->route('login')->with('success', 'If an account exists with that email, a login link has been sent.');
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
            $this->auditLogin(null, 'magic_link_failed', $request->ip(), [
                'email' => $email, 'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with('error', 'Unable to send email. Please contact your league manager.');
        }

        $this->auditLogin($user->id, 'magic_link_sent', $request->ip(), ['email' => $email]);

        return redirect()->route('login')->with('success', 'If an account exists with that email, a login link has been sent.');
    }

    public function verify(Request $request, string $token)
    {
        $magicLink = MagicLink::where('token', $token)->first();

        if (! $magicLink) {
            $this->auditLogin(null, 'login_failed', $request->ip(), ['method' => 'magic_link', 'reason' => 'invalid_token']);
            return Inertia::render('Auth/MagicLinkExpired', ['reason' => 'invalid']);
        }

        if ($magicLink->used_at) {
            $this->auditLogin(null, 'login_failed', $request->ip(), [
                'method' => 'magic_link', 'reason' => 'already_used', 'email' => $magicLink->email,
                'link_created' => $magicLink->created_at->toDateTimeString(),
                'link_used' => $magicLink->used_at->toDateTimeString(),
                'created_by' => $magicLink->created_by ? 'admin' : 'self',
            ]);
            return Inertia::render('Auth/MagicLinkExpired', ['reason' => 'expired', 'email' => $magicLink->email]);
        }

        if (! $magicLink->isValid()) {
            $this->auditLogin(null, 'login_failed', $request->ip(), [
                'method' => 'magic_link', 'reason' => 'expired', 'email' => $magicLink->email,
                'link_created' => $magicLink->created_at->toDateTimeString(),
                'expired_at' => $magicLink->expires_at->toDateTimeString(),
                'age_minutes' => (int) $magicLink->created_at->diffInMinutes(now()),
                'created_by' => $magicLink->created_by ? 'admin' : 'self',
            ]);
            return Inertia::render('Auth/MagicLinkExpired', ['reason' => 'expired', 'email' => $magicLink->email]);
        }

        $user = User::where('email', $magicLink->email)->first();

        if (! $user) {
            $this->auditLogin(null, 'login_failed', $request->ip(), ['method' => 'magic_link', 'reason' => 'no_account', 'email' => $magicLink->email]);
            return Inertia::render('Auth/MagicLinkExpired', ['reason' => 'no_account']);
        }

        // Mark email as verified if not already
        if (! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        $magicLink->markUsed();
        $user->update(['last_login_at' => now()]);

        Auth::login($user, remember: true);

        $this->auditLogin($user->id, 'login', $request->ip(), ['method' => 'magic_link']);

        return redirect()->route('leagues.index');
    }

    protected function auditLogin(?int $userId, string $action, ?string $ip, array $details = []): void
    {
        AuditLog::withoutGlobalScopes()->create([
            'league_id' => null,
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => null,
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => $details,
            'ip_address' => $ip,
        ]);
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
