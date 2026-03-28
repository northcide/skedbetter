<?php

namespace App\Enums;

enum ConstraintType: string
{
    case MaxWeeklySlots = 'max_weekly_slots';
    case TimeBlockLength = 'time_block_length';
    case MinGapBetweenSlots = 'min_gap_between_slots';
    case MaxDailySlots = 'max_daily_slots';
    case EarliestStartTime = 'earliest_start_time';
    case LatestEndTime = 'latest_end_time';
    case AllowedDaysOfWeek = 'allowed_days_of_week';
}
