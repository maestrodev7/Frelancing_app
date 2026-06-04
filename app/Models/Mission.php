<?php

namespace App\Models;

use App\Domain\Missions\Enums\MissionStatus;
use App\Domain\Missions\Enums\MissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'accepted_proposal_id',
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

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function acceptedProposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'accepted_proposal_id');
    }

    /**
     * @return HasOne<Dispute, $this>
     */
    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }

    /**
     * @return HasMany<MissionReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(MissionReview::class);
    }

    public function isOpen(): bool
    {
        return $this->status === MissionStatus::Open;
    }

    public function isInProgress(): bool
    {
        return $this->status === MissionStatus::InProgress;
    }

    public function isDisputed(): bool
    {
        return $this->status === MissionStatus::Disputed;
    }

    public function isClosed(): bool
    {
        return $this->status === MissionStatus::Closed;
    }

    public function freelancerUserId(): ?int
    {
        return $this->acceptedProposal?->user_id;
    }
}
