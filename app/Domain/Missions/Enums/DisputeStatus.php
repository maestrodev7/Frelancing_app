<?php

namespace App\Domain\Missions\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
}
