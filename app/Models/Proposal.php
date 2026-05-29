<?php

namespace App\Models;

use App\Domain\Missions\Enums\PricingType;
use App\Domain\Missions\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    protected $fillable = [
        'mission_id',
        'user_id',
        'cover_letter',
        'pricing_type',
        'amount_fixed',
        'hourly_rate',
        'estimated_hours',
        'delivery_days',
        'status',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pricing_type' => PricingType::class,
            'status' => ProposalStatus::class,
            'amount_fixed' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'estimated_hours' => 'integer',
            'delivery_days' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Mission, $this>
     */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPending(): bool
    {
        return $this->status === ProposalStatus::Pending;
    }
}
