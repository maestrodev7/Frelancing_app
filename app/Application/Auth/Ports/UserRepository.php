<?php

namespace App\Application\Auth\Ports;

use App\Domain\Users\Enums\UserRole;
use App\Models\User;

interface UserRepository
{
    public function create(
        string $name,
        string $email,
        string $phone,
        int $countryId,
        string $hashedPassword,
        UserRole $role,
    ): User;
}
