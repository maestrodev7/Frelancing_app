<?php

namespace App\Domain\Missions\Enums;

enum MissionStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Disputed = 'disputed';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
