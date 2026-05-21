<?php

namespace App\Infrastructure\Persistence;

use App\Application\Auth\Ports\ClientProfileRepository;
use App\Models\ClientProfile;

class EloquentClientProfileRepository implements ClientProfileRepository
{
    public function create(
        int $userId,
        string $billingAddress,
        string $timezone,
    ): ClientProfile {
        return ClientProfile::create([
            'user_id' => $userId,
            'billing_address' => $billingAddress,
            'timezone' => $timezone,
        ]);
    }
}
