<?php

namespace App\Enums;

enum BlackoutRecurrence: string
{
    case None = 'none';
    case Weekly = 'weekly';
    case Yearly = 'yearly';
}
