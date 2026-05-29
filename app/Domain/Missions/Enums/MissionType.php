<?php

namespace App\Domain\Missions\Enums;

enum MissionType: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';
}
