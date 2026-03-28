<?php

namespace App\Enums;

enum ScheduleEntryStatus: string
{
    case Confirmed = 'confirmed';
    case Tentative = 'tentative';
    case Cancelled = 'cancelled';
}
