<?php

namespace App\Services\Payments;

use App\Domain\Missions\Enums\MissionStatus;
use App\Domain\Missions\Enums\ProposalStatus;
use App\Domain\Payments\Enums\PaymentMethod;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Models\Mission;
use App\Models\MissionPayment;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MissionPaymentService
{
    public function __construct(
        private readonly KratosPayClient $kratos,
    ) {}

    public function calculateProposalAmount(Proposal $proposal): float
    {
        if ($proposal->pricing_type->value === 'fixed') {
            return (float) $proposal->amount_fixed;
        }

        return (float) $proposal->hourly_rate * (int) $proposal->estimated_hours;
    }

    public function platformFee(float $amount): float
    {
        $percent = (float) config('kratospay.platform_fee_percent', 5);

        return round($amount * ($percent / 100), 2);
    }

    public function refundFee(float $amount): float
    {
        $percent = (float) config('kratospay.refund_fee_percent', 3);

        return round($amount * ($percent / 100), 2);
    }

    public function missionCurrency(Proposal $proposal): string
    {
        return strtoupper((string) ($proposal->mission->currency ?? 'XAF'));
    }

    public function amountInXaf(float $amount, string $currency): float
    {
        $currency = strtoupper($currency);

        if ($currency === 'XAF') {
            return round($amount, 0);
        }

        $rates = config('kratospay.exchange_rates_to_xaf', []);
        $rate = $rates[$currency] ?? null;

        if ($rate === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => 'Les paiements Orange / MTN passent en XAF. Modifiez la mission et utilisez la devise XAF.',
            ]);
        }

        return round($amount * (float) $rate, 0);
    }

    /**
     * @return array{min: float, max: float, valid: bool, message: string|null, amount_xaf: float}
     */
    public function validateAmountForProposal(Proposal $proposal, PaymentMethod $method): array
    {
        $amount = $this->calculateProposalAmount($proposal);
        $currency = $this->missionCurrency($proposal);
        $amountXaf = $this->amountInXaf($amount, $currency);

        return $this->validateAmount($amountXaf, $method, $amount, $currency);
    }

    /**
     * @return array{min: float, max: float, valid: bool, message: string|null, amount_xaf: float}
     */
    public function validateAmount(
        float $amountXaf,
        PaymentMethod $method,
        ?float $displayAmount = null,
        ?string $displayCurrency = null,
    ): array {
        $limits = $this->kratos->amountLimits();
        $displayAmount ??= $amountXaf;
        $displayCurrency ??= 'XAF';
        $proposalLabel = $displayCurrency === 'XAF'
            ? sprintf('%s XAF', number_format($displayAmount, 0, ',', ' '))
            : sprintf(
                '%s %s (≈ %s XAF)',
                number_format($displayAmount, 0, ',', ' '),
                $displayCurrency,
                number_format($amountXaf, 0, ',', ' '),
            );

        if ($amountXaf < $limits['min']) {
            return [
                ...$limits,
                'amount_xaf' => $amountXaf,
                'valid' => false,
                'message' => sprintf(
                    'Le montant minimum pour %s est %s XAF. La proposition actuelle est de %s.',
                    $method->label(),
                    number_format($limits['min'], 0, ',', ' '),
                    $proposalLabel,
                ),
            ];
        }

        if ($amountXaf > $limits['max']) {
            return [
                ...$limits,
                'amount_xaf' => $amountXaf,
                'valid' => false,
                'message' => sprintf(
                    'Le montant maximum pour %s est %s XAF. La proposition actuelle est de %s.',
                    $method->label(),
                    number_format($limits['max'], 0, ',', ' '),
                    $proposalLabel,
                ),
            ];
        }

        return [
            ...$limits,
            'amount_xaf' => $amountXaf,
            'valid' => true,
            'message' => null,
        ];
    }

    /**
     * @return array{payment: MissionPayment, kratos: array<string, mixed>}
     */
    public function initiate(Proposal $proposal, PaymentMethod $method, string $payerPhone): array
    {
        $proposal->loadMissing('mission', 'freelancer');

        abort_unless($proposal->isPending(), 403, 'Cette proposition ne peut plus être payée.');
        abort_unless(
            $proposal->mission->isOpen() || $proposal->mission->isAwaitingPayment(),
            403,
            'La mission n\'accepte plus de paiement.',
        );

        $amount = $this->calculateProposalAmount($proposal);
        $currency = $this->missionCurrency($proposal);
        $amountXaf = $this->amountInXaf($amount, $currency);
        abort_if($amount <= 0, 422, 'Montant de proposition invalide.');

        $validation = $this->validateAmount($amountXaf, $method, $amount, $currency);
        if (! $validation['valid']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => $validation['message'],
            ]);
        }

        $platformFee = $this->platformFee($amount);
        $netFreelancer = round($amount - $platformFee, 2);

        $payment = MissionPayment::query()
            ->where('proposal_id', $proposal->id)
            ->whereIn('status', [PaymentStatus::Pending, PaymentStatus::Processing, PaymentStatus::Escrowed])
            ->first();

        if ($payment === null) {
            $payment = MissionPayment::create([
                'mission_id' => $proposal->mission_id,
                'proposal_id' => $proposal->id,
                'client_id' => $proposal->mission->user_id,
                'freelancer_id' => $proposal->user_id,
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'net_freelancer_amount' => $netFreelancer,
                'currency' => $currency,
                'payment_method' => $method,
                'status' => PaymentStatus::Pending,
                'payer_phone' => $this->kratos->normalizePhone($payerPhone),
                'metadata' => ['amount_xaf' => $amountXaf],
            ]);

            $proposal->mission->update(['status' => MissionStatus::AwaitingPayment]);
        } else {
            $payment->update([
                'payment_method' => $method,
                'payer_phone' => $this->kratos->normalizePhone($payerPhone),
                'status' => PaymentStatus::Pending,
                'metadata' => array_merge($payment->metadata ?? [], ['amount_xaf' => $amountXaf]),
            ]);
        }

        $kratosResponse = $method === PaymentMethod::Card
            ? $this->kratos->depositCard($amountXaf)
            : $this->kratos->depositMobileMoney(
                $method->kratosValue(),
                $amountXaf,
                $payerPhone,
            );

        $reference = (string) ($kratosResponse['reference'] ?? data_get($kratosResponse, 'transaction.reference'));
        $transactionId = data_get($kratosResponse, 'transaction.id');
        $sessionId = data_get($kratosResponse, 'data.sessionId');

        $payment->update([
            'status' => PaymentStatus::Processing,
            'kratos_reference' => $reference !== '' ? $reference : null,
            'kratos_transaction_id' => is_numeric($transactionId) ? (int) $transactionId : null,
            'card_session_id' => is_string($sessionId) ? $sessionId : null,
            'metadata' => array_merge($payment->metadata ?? [], ['init_response' => $kratosResponse]),
        ]);

        return ['payment' => $payment->fresh(), 'kratos' => $kratosResponse];
    }

    public function syncStatus(MissionPayment $payment): MissionPayment
    {
        if ($payment->kratos_reference === null) {
            return $payment;
        }

        if ($payment->status === PaymentStatus::Escrowed) {
            return $payment;
        }

        if ($payment->status === PaymentStatus::Failed) {
            return $payment;
        }

        try {
            $payload = $this->kratos->getTransactionByReference($payment->kratos_reference);
        } catch (\Throwable $e) {
            $payment->update([
                'metadata' => array_merge($payment->metadata ?? [], [
                    'last_sync_error' => $e->getMessage(),
                    'last_sync_at' => now()->toIso8601String(),
                ]),
            ]);

            return $payment->fresh();
        }

        $kratosStatus = $this->kratos->transactionStatus($payload);

        if ($this->kratos->isTransactionSuccessful($payload)) {
            return $this->markEscrowed($payment);
        }

        if ($this->kratos->isTransactionFailed($payload)) {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'failure_reason' => (string) ($payload['message'] ?? 'Paiement échoué'),
                'metadata' => array_merge($payment->metadata ?? [], ['last_status' => $payload]),
            ]);

            if ($payment->mission->status === MissionStatus::AwaitingPayment) {
                $payment->mission->update(['status' => MissionStatus::Open]);
            }
        } else {
            $payment->update([
                'metadata' => array_merge($payment->metadata ?? [], [
                    'last_status' => $payload,
                    'kratos_status' => $kratosStatus,
                    'last_sync_at' => now()->toIso8601String(),
                ]),
            ]);
        }

        return $payment->fresh();
    }

    public function markEscrowed(MissionPayment $payment): MissionPayment
    {
        if ($payment->status === PaymentStatus::Escrowed) {
            return $payment;
        }

        DB::transaction(function () use ($payment): void {
            $payment->update([
                'status' => PaymentStatus::Escrowed,
                'paid_at' => now(),
            ]);

            $proposal = $payment->proposal()->with('mission.client.clientProfile')->first();
            $mission = $proposal->mission;

            $proposal->update(['status' => ProposalStatus::Accepted]);
            $mission->update([
                'status' => MissionStatus::InProgress,
                'accepted_proposal_id' => $proposal->id,
            ]);

            $mission->proposals()
                ->where('id', '!=', $proposal->id)
                ->where('status', ProposalStatus::Pending)
                ->update(['status' => ProposalStatus::Rejected]);

            $mission->client?->clientProfile?->increment('total_spent', $payment->amount);
        });

        return $payment->fresh();
    }

    public function releaseToFreelancer(MissionPayment $payment): void
    {
        abort_unless($payment->isEscrowed(), 403, 'Les fonds ne sont pas en escrow.');

        $freelancer = $payment->freelancer;
        $phone = $freelancer->phone;

        if ($phone === null || $phone === '') {
            throw new RuntimeException('Le freelance doit renseigner un numéro de téléphone pour le paiement.');
        }

        $method = $payment->payment_method === PaymentMethod::Card
            ? PaymentMethod::MtnMoney
            : $payment->payment_method;

        $response = $this->kratos->withdraw(
            $method->kratosValue(),
            (float) $payment->net_freelancer_amount,
            $phone,
            $freelancer->name,
        );

        $payment->update([
            'status' => PaymentStatus::Released,
            'released_at' => now(),
            'metadata' => array_merge($payment->metadata ?? [], ['release_response' => $response]),
        ]);
    }

    public function refundToClient(MissionPayment $payment): void
    {
        abort_unless($payment->isEscrowed(), 403, 'Les fonds ne sont pas en escrow.');

        $client = $payment->client;
        $phone = $payment->payer_phone ?? $client->phone;

        if ($phone === null || $phone === '') {
            throw new RuntimeException('Numéro client manquant pour le remboursement.');
        }

        $fee = $this->refundFee((float) $payment->amount);
        $refundAmount = max(0, round((float) $payment->amount - $fee, 2));

        $method = $payment->payment_method === PaymentMethod::Card
            ? PaymentMethod::MtnMoney
            : $payment->payment_method;

        $response = $this->kratos->withdraw(
            $method->kratosValue(),
            $refundAmount,
            $phone,
            $client->name,
        );

        $payment->update([
            'status' => PaymentStatus::Refunded,
            'refunded_at' => now(),
            'refund_fee' => $fee,
            'metadata' => array_merge($payment->metadata ?? [], ['refund_response' => $response]),
        ]);
    }

    public function escrowForMission(Mission $mission): ?MissionPayment
    {
        return MissionPayment::query()
            ->where('mission_id', $mission->id)
            ->where('status', PaymentStatus::Escrowed)
            ->latest()
            ->first();
    }
}
