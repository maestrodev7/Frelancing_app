<?php

namespace App\Application\Auth\Data;

readonly class RegisterFreelancerData
{
    /**
     * @param  array<int, array{name: string, level: int}>  $skills
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $password,
        public int $countryId,
        public string $title,
        public string $bio,
        public ?string $hourlyRateDefault,
        public string $currency,
        public int $experienceYears,
        public string $availabilityStatus,
        public string $timezone,
        public ?string $portfolioUrl,
        public ?string $linkedinUrl,
        public array $skills,
    ) {}
}
