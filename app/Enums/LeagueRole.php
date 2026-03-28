<?php

namespace App\Enums;

enum LeagueRole: string
{
    case DivisionManager = 'division_manager';
    case Coach = 'coach';

    public function label(): string
    {
        return match ($this) {
            self::DivisionManager => 'Division Manager',
            self::Coach => 'Coach',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::DivisionManager => 1,
            self::Coach => 2,
        };
    }
}
