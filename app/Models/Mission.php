<?php

namespace App\Models;

use App\Domain\Missions\Enums\MissionStatus;
use App\Domain\Missions\Enums\MissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'budget_min',
        'budget_max',
        'hourly_cap',
        'currency',
        'status',
        'start_expected_at',
        'deadline_at',
        'is_moderated',
        'moderation_status',
        'moderated_by_admin_id',
        'moderated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MissionType::class,
            'status' => MissionStatus::class,
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'hourly_cap' => 'decimal:2',
            'start_expected_at' => 'datetime',
            'deadline_at' => 'datetime',
            'is_moderated' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Proposal, $this>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function isOpen(): bool
    {
        return $this->status === MissionStatus::Open;
    }
}
