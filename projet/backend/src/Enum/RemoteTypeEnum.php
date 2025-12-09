<?php

namespace App\Enum;

enum RemoteTypeEnum: string
{
    case ONSITE = 'onsite';
    case REMOTE = 'remote';
    case HYBRID = 'hybrid';
}
