<?php

namespace App\Infrastructure\Persistence;

use App\Application\Auth\Ports\UserRepository;
use App\Domain\Users\Enums\AccountStatus;
use App\Domain\Users\Enums\UserRole;
use App\Models\User;

class EloquentUserRepository implements UserRepository
{
    public function create(
        string $name,
        string $email,
        string $phone,
        int $countryId,
        string $hashedPassword,
        UserRole $role,
    ): User {
        return User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'country_id' => $countryId,
            'password' => $hashedPassword,
            'role' => $role,
            'status' => AccountStatus::Active,
        ]);
    }
}
