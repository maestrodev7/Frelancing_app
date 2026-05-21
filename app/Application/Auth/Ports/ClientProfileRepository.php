<?php

namespace App\Application\Auth\Ports;

use App\Models\ClientProfile;

interface ClientProfileRepository
{
    public function create(
        int $userId,
        string $billingAddress,
        string $timezone,
    ): ClientProfile;
}
