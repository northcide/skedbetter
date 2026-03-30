<?php

namespace App\Http\Middleware;

use App\Models\League;
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
            if ($request->routeIs('logout', 'leagues.index', 'checkout.*')) {
                return $next($request);
            }

            // Check if user has a pending league with Stripe (payment incomplete)
            $pendingLeague = League::where('requested_by', $user->id)
                ->whereNull('approved_at')
                ->whereNotNull('stripe_id')
                ->first();

            return Inertia::render('Auth/PendingApproval', [
                'pendingLeague' => $pendingLeague ? [
                    'slug' => $pendingLeague->slug,
                    'stripe_id' => $pendingLeague->stripe_id,
                    'has_active_plan' => $pendingLeague->hasActivePlan(),
                ] : null,
            ])->toResponse($request);
        }

        return $next($request);
    }
}
