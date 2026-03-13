<?php

namespace App\Enums;

enum MessageStrength: int
{
    case ERROR = 0;
    case VERY_WEAK = 1;
    case WEAK = 2;
    case NORMAL = 3;
    case STRONG = 4;
    case VERY_STRONG = 5;
    case EXACT = 6;
}
