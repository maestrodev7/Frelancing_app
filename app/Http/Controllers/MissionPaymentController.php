<?php

namespace App\Http\Controllers;

use App\Domain\Payments\Enums\PaymentMethod;
use App\Http\Requests\InitiateMissionPaymentRequest;
use App\Models\MissionPayment;
use App\Models\Proposal;
use App\Services\Payments\MissionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MissionPaymentController extends Controller
{
    public function __construct(
        private readonly MissionPaymentService $payments,
    ) {}

    public function checkout(Request $request, Proposal $proposal): Response
    {
        $this->authorizeClient($request, $proposal);

        abort_unless($proposal->isPending(), 403);
        abort_unless($proposal->mission->isOpen() || $proposal->mission->isAwaitingPayment(), 403);

        $amount = $this->payments->calculateProposalAmount($proposal);
        $currency = strtoupper((string) ($proposal->mission->currency ?? 'XAF'));
        $amountXaf = $this->payments->amountInXaf($amount, $currency);
        $platformFee = $this->payments->platformFee($amount);
        $limits = $this->payments->validateAmount($amountXaf, PaymentMethod::OrangeMoney, $amount, $currency);

        return Inertia::render('Missions/Payment', [
            'proposal' => [
                'id' => $proposal->id,
                'amount' => $amount,
                'amount_xaf' => $amountXaf,
                'platform_fee' => $platformFee,
                'total' => $amount,
                'currency' => $currency,
                'freelancer_name' => $proposal->freelancer?->name,
                'mission' => [
                    'id' => $proposal->mission_id,
                    'title' => $proposal->mission->title,
                ],
            ],
            'amountLimits' => [
                'min' => $limits['min'],
                'max' => $limits['max'],
                'valid' => $limits['valid'],
                'message' => $limits['message'],
            ],
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $m) => $m->uiMeta()),
            'clientPhone' => $request->user()->phone,
        ]);
    }

    public function store(InitiateMissionPaymentRequest $request, Proposal $proposal): RedirectResponse
    {
        $method = PaymentMethod::from($request->validated('payment_method'));
        $phone = $request->validated('payer_phone') ?? $request->user()->phone ?? '';

        if ($method !== PaymentMethod::Card && $phone === '') {
            return back()->withErrors([
                'payer_phone' => 'Indiquez votre numéro Mobile Money (Orange ou MTN).',
            ]);
        }

        $result = $this->payments->initiate($proposal, $method, $phone);
        $payment = $result['payment'];

        return redirect()
            ->route('missions.payments.waiting', $payment)
            ->with('status', 'payment-initiated');
    }

    public function waiting(Request $request, MissionPayment $payment): Response
    {
        $this->authorizePaymentOwner($request, $payment);

        $payment = $this->payments->syncStatus($payment);

        return Inertia::render('Missions/PaymentWaiting', [
            'payment' => $this->paymentPayload($payment),
            'mission' => [
                'id' => $payment->mission_id,
                'title' => $payment->mission->title,
            ],
        ]);
    }

    public function status(Request $request, MissionPayment $payment): JsonResponse
    {
        $this->authorizePaymentOwner($request, $payment);

        $payment = $this->payments->syncStatus($payment);

        return response()->json($this->paymentPayload($payment));
    }

    private function authorizeClient(Request $request, Proposal $proposal): void
    {
        abort_unless($request->user()?->isClient(), 403);
        $proposal->loadMissing('mission', 'freelancer');
        abort_unless($proposal->mission->user_id === $request->user()->id, 403);
    }

    private function authorizePaymentOwner(Request $request, MissionPayment $payment): void
    {
        abort_unless($payment->client_id === $request->user()?->id, 403);
        $payment->loadMissing('mission', 'proposal.freelancer');
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(MissionPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'status' => $payment->status->value,
            'status_label' => match ($payment->status->value) {
                'pending' => 'En attente',
                'processing' => 'En cours de traitement',
                'escrowed' => 'Payé — fonds sécurisés',
                'failed' => 'Échoué',
                'released' => 'Versé au freelance',
                'refunded' => 'Remboursé',
                default => $payment->status->value,
            },
            'amount' => $payment->amount,
            'platform_fee' => $payment->platform_fee,
            'currency' => $payment->currency,
            'payment_method' => $payment->payment_method->label(),
            'kratos_reference' => $payment->kratos_reference,
            'kratos_status' => data_get($payment->metadata, 'kratos_status'),
            'card_session_id' => $payment->card_session_id,
            'failure_reason' => $payment->failure_reason,
            'mission_id' => $payment->mission_id,
            'is_complete' => $payment->status->value === 'escrowed',
            'is_failed' => $payment->status->value === 'failed',
        ];
    }
}
