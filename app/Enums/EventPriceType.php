<?php

namespace App\Enums;

enum EventPriceType: string
{
    case EarlyBird = 'early_bird';
    case Regular = 'regular';
    case LateBird = 'late_bird';
}
