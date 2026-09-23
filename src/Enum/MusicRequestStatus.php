<?php

namespace App\Enum;

enum MusicRequestStatus: string
{
    case PENDING = 'PENDING';
    case VALIDATED = 'VALIDATED';
    case PLAYED = 'PLAYED';
    case REJECTED = 'REJECTED';
}