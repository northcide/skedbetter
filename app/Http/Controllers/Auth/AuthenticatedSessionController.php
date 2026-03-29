<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            AuditLog::withoutGlobalScopes()->create([
                'league_id' => null,
                'user_id' => null,
                'action' => 'login_failed',
                'auditable_type' => null,
                'auditable_id' => null,
                'new_values' => ['method' => 'password', 'email' => $request->email],
                'ip_address' => $request->ip(),
            ]);
            throw $e;
        }

        $request->session()->regenerate();

        AuditLog::withoutGlobalScopes()->create([
            'league_id' => null,
            'user_id' => Auth::id(),
            'action' => 'login',
            'auditable_type' => null,
            'auditable_id' => null,
            'new_values' => ['method' => 'password'],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
