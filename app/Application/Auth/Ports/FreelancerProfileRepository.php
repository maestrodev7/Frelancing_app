<?php

namespace App\Application\Auth\Ports;

use App\Models\FreelancerProfile;

interface FreelancerProfileRepository
{
    /**
     * @param  array<int, array{name: string, level: int}>  $skills
     */
    public function create(
        int $userId,
        string $title,
        string $bio,
        ?string $hourlyRateDefault,
        string $currency,
        int $experienceYears,
        string $availabilityStatus,
        string $timezone,
        ?string $portfolioUrl,
        ?string $linkedinUrl,
        array $skills,
    ): FreelancerProfile;
}
