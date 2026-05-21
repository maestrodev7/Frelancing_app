<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\Data\RegisterClientData;
use App\Application\Auth\Ports\ClientProfileRepository;
use App\Application\Auth\Ports\PasswordHasher;
use App\Application\Auth\Ports\UserRepository;
use App\Application\Shared\Ports\TransactionManager;
use App\Domain\Users\Enums\UserRole;
use App\Models\User;

class RegisterClientUseCase
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly ClientProfileRepository $clientProfiles,
        private readonly PasswordHasher $passwordHasher,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(RegisterClientData $data): User
    {
        return $this->transactions->run(function () use ($data): User {
            $user = $this->users->create(
                name: $data->name,
                email: $data->email,
                phone: $data->phone,
                countryId: $data->countryId,
                hashedPassword: $this->passwordHasher->hash($data->password),
                role: UserRole::Client,
            );

            $this->clientProfiles->create(
                userId: $user->id,
                billingAddress: $data->billingAddress,
                timezone: $data->timezone,
            );

            return $user;
        });
    }
}
