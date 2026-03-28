<?php

namespace App\Enums;

enum LeagueRole: string
{
    case LeagueManager = 'league_manager';
    case DivisionManager = 'division_manager';
    case TeamManager = 'team_manager';
    case Coach = 'coach';

    public function label(): string
    {
        return match ($this) {
            self::LeagueManager => 'League Manager',
            self::DivisionManager => 'Division Manager',
            self::TeamManager => 'Team Manager',
            self::Coach => 'Coach',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::LeagueManager => 1,
            self::DivisionManager => 2,
            self::TeamManager => 3,
            self::Coach => 4,
        };
    }
}
