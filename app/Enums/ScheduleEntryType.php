<?php

namespace App\Enums;

enum ScheduleEntryType: string
{
    case Practice = 'practice';
    case Game = 'game';
    case Scrimmage = 'scrimmage';
    case Tournament = 'tournament';
    case Other = 'other';
}
