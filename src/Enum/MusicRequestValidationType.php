<?php

namespace App\Enum;

enum MusicRequestValidationType: string
{
    case VALIDATED_BY_VOTES = 'VALIDATED_BY_VOTES';
    case PAID = 'PAID';
}