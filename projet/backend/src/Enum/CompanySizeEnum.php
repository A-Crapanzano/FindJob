<?php

namespace App\Enum;

enum CompanySizeEnum: string
{
    case STARTUP = 'startup';
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';
    case ENTERPRISE = 'enterprise';
}
