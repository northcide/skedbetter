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

            // Check league membership
            if ($user) {
                $membership = $user->leagues()
                    ->where('leagues.id', $league->id)
                    ->first();

                if (! $membership) {
                    abort(403, 'You do not have access to this league.');
                }

                $this->context->set($league, $membership->pivot->role);
            } else {
                $this->context->set($league);
            }
        }

        return $next($request);
    }
}
