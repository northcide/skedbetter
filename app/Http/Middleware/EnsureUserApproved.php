<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isApproved() && !$user->isSuperadmin()) {
            // Allow logout and viewing leagues
            if ($request->routeIs('logout', 'leagues.index')) {
                return $next($request);
            }

            return Inertia::render('Auth/PendingApproval')->toResponse($request);
        }

        return $next($request);
    }
}
