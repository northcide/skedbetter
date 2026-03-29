<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        AuditLog::withoutGlobalScopes()->create([
            'league_id' => null,
            'user_id' => $user->id,
            'action' => 'registration',
            'auditable_type' => null,
            'auditable_id' => null,
            'new_values' => ['name' => $user->name, 'email' => $user->email],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('login')->with('status', 'Your account has been created and is pending approval. You will be notified when it is approved.');
    }
}
