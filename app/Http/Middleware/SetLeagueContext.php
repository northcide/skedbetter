<?php

namespace App\Http\Middleware;

use App\Models\League;
use App\Services\LeagueContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLeagueContext
{
    public function __construct(protected LeagueContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $league = $request->route('league');

        if ($league) {
            if (is_string($league)) {
                $league = League::where('slug', $league)->firstOrFail();
            }

            $user = $request->user();

            // Superadmins can access any league
            if ($user && $user->isSuperadmin()) {
                $this->context->set($league, 'superadmin');
                return $next($request);
            }

            // Block access to unapproved leagues
            if (!$league->isApproved()) {
                abort(403, 'This league is pending approval.');
            }

            // Handle lapsed subscriptions (has Stripe but inactive)
            if ($league->stripe_id && !$league->is_active) {
                $userRole = $user ? $user->leagues()
                    ->where('leagues.id', $league->id)
                    ->pluck('league_user.role')
                    ->first() : null;

                if ($userRole === 'league_admin') {
                    return redirect()->route('leagues.billing', $league->slug);
                }

                abort(403, 'This league\'s subscription is inactive.');
            }

            // Check league membership — pick highest-level role
            if ($user) {
                $roles = $user->leagues()
                    ->where('leagues.id', $league->id)
                    ->get()
                    ->pluck('pivot.role')
                    ->toArray();

                if (empty($roles)) {
                    abort(403, 'You do not have access to this league.');
                }

                // Priority: league_admin > division_manager > coach
                $rolePriority = ['league_admin' => 1, 'division_manager' => 2, 'coach' => 3];
                usort($roles, fn($a, $b) => ($rolePriority[$a] ?? 99) - ($rolePriority[$b] ?? 99));

                $this->context->set($league, $roles[0]);
            } else {
                $this->context->set($league);
            }
        }

        return $next($request);
    }
}
