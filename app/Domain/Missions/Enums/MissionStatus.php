<?php

namespace App\Domain\Missions\Enums;

enum MissionStatus: string
{
    case Open = 'open';
    case AwaitingPayment = 'awaiting_payment';
    case InProgress = 'in_progress';
    case Disputed = 'disputed';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
