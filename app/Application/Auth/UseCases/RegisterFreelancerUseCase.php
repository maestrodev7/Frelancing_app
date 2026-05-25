<?php

namespace App\Application\Auth\UseCases;

use App\Application\Auth\Data\RegisterFreelancerData;
use App\Application\Auth\Ports\FreelancerProfileRepository;
use App\Application\Auth\Ports\PasswordHasher;
use App\Application\Auth\Ports\UserRepository;
use App\Application\Shared\Ports\TransactionManager;
use App\Domain\Users\Enums\UserRole;
use App\Models\User;

class RegisterFreelancerUseCase
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly FreelancerProfileRepository $freelancerProfiles,
        private readonly PasswordHasher $passwordHasher,
        private readonly TransactionManager $transactions,
    ) {}

    public function execute(RegisterFreelancerData $data): User
    {
        return $this->transactions->run(function () use ($data): User {
            $user = $this->users->create(
                name: $data->name,
                email: $data->email,
                phone: $data->phone,
                countryId: $data->countryId,
                hashedPassword: $this->passwordHasher->hash($data->password),
                role: UserRole::Freelancer,
            );

            $this->freelancerProfiles->create(
                userId: $user->id,
                title: $data->title,
                bio: $data->bio,
                hourlyRateDefault: $data->hourlyRateDefault,
                currency: $data->currency,
                experienceYears: $data->experienceYears,
                availabilityStatus: $data->availabilityStatus,
                timezone: $data->timezone,
                portfolioUrl: $data->portfolioUrl,
                linkedinUrl: $data->linkedinUrl,
                skills: $data->skills,
            );

            return $user;
        });
    }
}
