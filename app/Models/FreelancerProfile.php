<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FreelancerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'bio',
        'hourly_rate_default',
        'currency',
        'experience_years',
        'average_rating',
        'completed_jobs_count',
        'availability_status',
        'timezone',
        'portfolio_url',
        'linkedin_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hourly_rate_default' => 'decimal:2',
            'average_rating' => 'decimal:2',
            'experience_years' => 'integer',
            'completed_jobs_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'freelancer_skills')
            ->withPivot('level')
            ->withTimestamps();
    }
}
