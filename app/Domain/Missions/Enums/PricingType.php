<?php

namespace App\Domain\Missions\Enums;

enum PricingType: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';
}
