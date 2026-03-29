<?php

namespace App\Http\Middleware;

use App\Services\LeagueContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLeagueManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(LeagueContext::class);
        $role = $context->userRole();

        if (! in_array($role, ['superadmin', 'league_admin', 'division_manager'])) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
