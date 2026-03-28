<?php

namespace App\Enums;

enum SurfaceType: string
{
    case Grass = 'grass';
    case Turf = 'turf';
    case Indoor = 'indoor';
    case Court = 'court';
}
