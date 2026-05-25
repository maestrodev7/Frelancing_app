<?php

namespace App\Infrastructure\Persistence;

use App\Application\Auth\Ports\FreelancerProfileRepository;
use App\Models\FreelancerProfile;
use App\Models\Skill;

class EloquentFreelancerProfileRepository implements FreelancerProfileRepository
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
    ): FreelancerProfile {
        $profile = FreelancerProfile::create([
            'user_id' => $userId,
            'title' => $title,
            'bio' => $bio,
            'hourly_rate_default' => $hourlyRateDefault,
            'currency' => $currency,
            'experience_years' => $experienceYears,
            'availability_status' => $availabilityStatus,
            'timezone' => $timezone,
            'portfolio_url' => $portfolioUrl,
            'linkedin_url' => $linkedinUrl,
        ]);

        $profile->skills()->sync(
            collect($skills)
                ->mapWithKeys(function (array $skill): array {
                    $skillModel = Skill::query()->firstOrCreate([
                        'name' => trim($skill['name']),
                    ]);

                    return [$skillModel->id => ['level' => $skill['level']]];
                })
                ->all()
        );

        return $profile->load('skills');
    }
}
