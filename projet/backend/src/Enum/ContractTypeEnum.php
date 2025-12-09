<?php

namespace App\Enum;

enum ContractTypeEnum: string
{
    case CDI = 'CDI';
    case CDD = 'CDD';
    case STAGE = 'Stage';
    case ALTERNANCE = 'Alternance';
}
