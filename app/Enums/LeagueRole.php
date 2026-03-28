<?php

namespace App\Enums;

enum LeagueRole: string
{
    case LeagueAdmin = 'league_admin';
    case DivisionManager = 'division_manager';
    case Coach = 'coach';

    public function label(): string
    {
        return match ($this) {
            self::LeagueAdmin => 'League Admin',
            self::DivisionManager => 'Division Manager',
            self::Coach => 'Coach',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::LeagueAdmin => 1,
            self::DivisionManager => 2,
            self::Coach => 3,
        };
    }
}
