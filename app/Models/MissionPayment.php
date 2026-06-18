<?php

namespace App\Models;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionPayment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'mission_id',
        'proposal_id',
        'client_id',
        'freelancer_id',
        'amount',
        'platform_fee',
        'net_freelancer_amount',
        'currency',
        'payment_method',
        'status',
        'payer_phone',
        'kratos_reference',
        'kratos_transaction_id',
        'card_session_id',
        'failure_reason',
        'paid_at',
        'released_at',
        'refunded_at',
        'refund_fee',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'net_freelancer_amount' => 'decimal:2',
            'refund_fee' => 'decimal:2',
            'paid_at' => 'datetime',
            'released_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
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
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function isEscrowed(): bool
    {
        return $this->status === PaymentStatus::Escrowed;
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, [PaymentStatus::Pending, PaymentStatus::Processing], true);
    }
}
