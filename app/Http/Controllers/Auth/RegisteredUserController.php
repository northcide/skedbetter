<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\AuditLog;
use App\Models\MagicLink;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'turnstileSiteKey' => Setting::get('turnstile_site_key', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Verify Turnstile CAPTCHA if configured
        $turnstileSecret = Setting::get('turnstile_secret_key', '');
        if ($turnstileSecret) {
            $token = $request->input('cf-turnstile-response', '');
            if (!$token) {
                return back()->withErrors(['captcha' => 'Please complete the CAPTCHA verification.']);
            }
            $response = \Illuminate\Support\Facades\Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $turnstileSecret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
            if (!$response->json('success')) {
                return back()->withErrors(['captcha' => 'CAPTCHA verification failed. Please try again.']);
            }
        }

        $email = strtolower(trim($request->email));

        // Check if user already exists (e.g. created by a league admin via associateCoach)
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            // Let them claim the account by setting their password
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|lowercase|email|max:255',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $existingUser->update([
                'name' => $request->name,
                'password' => Hash::make($request->password),
            ]);

            $user = $existingUser;
        } else {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $email,
                'password' => Hash::make($request->password),
            ]);
        }

        // If already a league admin, auto-approve
        $isLeagueAdmin = $user->leagues()
            ->wherePivot('role', 'league_admin')
            ->exists();

        if ($isLeagueAdmin && !$user->approved_at) {
            $user->update(['approved_at' => now(), 'email_verified_at' => now()]);
        }

        // Send verification magic link
        if (!$user->email_verified_at) {
            try {
                $magicLink = MagicLink::generate($email);
                Mail::to($email)->send(new MagicLinkMail($magicLink));
            } catch (\Exception $e) {
                // Don't fail registration if email can't be sent
            }
        }

        AuditLog::withoutGlobalScopes()->create([
            'league_id' => null,
            'user_id' => $user->id,
            'action' => 'registration',
            'auditable_type' => null,
            'auditable_id' => null,
            'new_values' => ['name' => $user->name, 'email' => $user->email, 'auto_approved' => $isLeagueAdmin],
            'ip_address' => $request->ip(),
        ]);

        if ($isLeagueAdmin) {
            return redirect()->route('login')->with('status', 'Your account is ready. Sign in to get started.');
        }

        return redirect()->route('login')->with('status', 'Please check your email to verify your address. Once verified, an administrator will activate your account.');
    }
}
