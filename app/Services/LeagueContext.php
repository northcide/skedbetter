<?php

namespace App\Services;

use App\Models\League;

class LeagueContext
{
    protected ?League $league = null;
    protected ?string $userRole = null;

    public function set(League $league, ?string $role = null): void
    {
        $this->league = $league;
        $this->userRole = $role;
    }

    public function league(): ?League
    {
        return $this->league;
    }

    public function hasLeague(): bool
    {
        return $this->league !== null;
    }

    public function userRole(): ?string
    {
        return $this->userRole;
    }
}
