<?php

namespace Tests\Feature;

use App\Domain\Missions\Enums\MissionStatus;
use App\Domain\Missions\Enums\ProposalStatus;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Users\Enums\UserRole;
use App\Models\Country;
use App\Models\Mission;
use App\Models\MissionPayment;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MissionPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'kratospay.base_url' => 'https://backendpay.kratospay.com',
            'kratospay.refresh_token' => 'test-refresh-token',
            'kratospay.payment_token' => 'test-payment-token',
            'kratospay.platform_fee_percent' => 5,
            'kratospay.refund_fee_percent' => 3,
        ]);
    }

    private function countryId(): int
    {
        return Country::query()->firstOrCreate(
            ['code' => 'CM'],
            ['name' => 'Cameroun'],
        )->id;
    }

    /**
     * @return array{0: User, 1: User, 2: Mission, 3: Proposal}
     */
    private function paymentScenario(): array
    {
        $client = User::factory()->create([
            'role' => UserRole::Client,
            'phone' => '677000001',
            'country_id' => $this->countryId(),
        ]);
        $freelancer = User::factory()->create([
            'role' => UserRole::Freelancer,
            'phone' => '677000002',
            'country_id' => $this->countryId(),
        ]);

        $mission = Mission::create([
            'user_id' => $client->id,
            'title' => 'Site web',
            'description' => 'Création site',
            'type' => 'fixed',
            'currency' => 'XAF',
            'status' => MissionStatus::Open,
            'moderation_status' => 'approved',
        ]);

        $proposal = Proposal::create([
            'mission_id' => $mission->id,
            'user_id' => $freelancer->id,
            'cover_letter' => 'Je suis disponible',
            'pricing_type' => 'fixed',
            'amount_fixed' => 1000,
            'delivery_days' => 7,
            'status' => ProposalStatus::Pending,
        ]);

        return [$client, $freelancer, $mission, $proposal];
    }

    public function test_client_can_open_payment_checkout(): void
    {
        [$client, , , $proposal] = $this->paymentScenario();

        $this->actingAs($client)
            ->get(route('proposals.payment.checkout', $proposal))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Missions/Payment'));
    }

    public function test_client_can_initiate_orange_money_payment_and_escrow(): void
    {
        [$client, $freelancer, $mission, $proposal] = $this->paymentScenario();

        Http::fake([
            'backendpay.kratospay.com/api/auth/refresh-token' => Http::response([
                'content' => 'fake-access-token',
                'status' => 200,
            ]),
            'backendpay.kratospay.com/api/wallet/public/deposit' => Http::response([
                'success' => true,
                'reference' => 'MP-TEST-001',
                'transaction' => [
                    'id' => 99,
                    'statut' => 'EN_ATTENTE',
                    'paymentMethod' => 'ORANGE_MONEY',
                ],
            ]),
            'backendpay.kratospay.com/api/transactions/reference/MP-TEST-001' => Http::response([
                'content' => [
                    'statut' => 'REUSSIE',
                    'reference' => 'MP-TEST-001',
                    'amount' => 1000,
                ],
            ]),
        ]);

        $this->actingAs($client)
            ->post(route('proposals.payment.store', $proposal), [
                'payment_method' => 'orange_money',
                'payer_phone' => '677000001',
            ])
            ->assertRedirect();

        $payment = MissionPayment::query()->where('proposal_id', $proposal->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(MissionStatus::AwaitingPayment, $mission->fresh()->status);

        $this->actingAs($client)
            ->getJson(route('missions.payments.status', $payment))
            ->assertOk()
            ->assertJsonPath('status', PaymentStatus::Escrowed->value);

        $this->assertSame(ProposalStatus::Accepted, $proposal->fresh()->status);
        $this->assertSame(MissionStatus::InProgress, $mission->fresh()->status);
        $this->assertSame($proposal->id, $mission->fresh()->accepted_proposal_id);
    }

    public function test_payment_rejects_amount_below_kratos_minimum(): void
    {
        [$client, , $mission, $proposal] = $this->paymentScenario();
        $proposal->update(['amount_fixed' => 50]);
        $mission->update(['currency' => 'XAF']);

        config(['kratospay.amount_min' => 100]);

        $this->actingAs($client)
            ->post(route('proposals.payment.store', $proposal), [
                'payment_method' => 'orange_money',
                'payer_phone' => '677000001',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_payment_converts_eur_proposal_to_xaf_for_kratos(): void
    {
        [$client, , $mission, $proposal] = $this->paymentScenario();
        $mission->update(['currency' => 'EUR']);
        $proposal->update(['amount_fixed' => 12]);

        Http::fake([
            'backendpay.kratospay.com/api/auth/refresh-token' => Http::response([
                'content' => 'fake-access-token',
            ]),
            'backendpay.kratospay.com/api/wallet/public/deposit' => Http::response([
                'success' => true,
                'reference' => 'MP-EUR-12',
                'transaction' => ['id' => 1, 'statut' => 'EN_ATTENTE'],
            ]),
        ]);

        $this->actingAs($client)
            ->post(route('proposals.payment.store', $proposal), [
                'payment_method' => 'orange_money',
                'payer_phone' => '677000001',
            ])
            ->assertRedirect();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/api/wallet/public/deposit')) {
                return false;
            }

            $amount = $request->data()['amount'] ?? 0;

            return $amount >= 7800 && $amount <= 7900;
        });
    }

    public function test_mission_close_releases_funds_to_freelancer(): void
    {
        [$client, $freelancer, $mission, $proposal] = $this->paymentScenario();

        $payment = MissionPayment::create([
            'mission_id' => $mission->id,
            'proposal_id' => $proposal->id,
            'client_id' => $client->id,
            'freelancer_id' => $freelancer->id,
            'amount' => 1000,
            'platform_fee' => 50,
            'net_freelancer_amount' => 950,
            'currency' => 'XAF',
            'payment_method' => 'orange_money',
            'status' => PaymentStatus::Escrowed,
            'payer_phone' => '677000001',
            'kratos_reference' => 'MP-PAID',
            'paid_at' => now(),
        ]);

        $mission->update([
            'status' => MissionStatus::InProgress,
            'accepted_proposal_id' => $proposal->id,
        ]);
        $proposal->update(['status' => ProposalStatus::Accepted]);

        Http::fake([
            'backendpay.kratospay.com/api/auth/refresh-token' => Http::response([
                'content' => 'fake-access-token',
            ]),
            'backendpay.kratospay.com/api/wallet/public/withdraw' => Http::response([
                'success' => true,
                'message' => 'Retrait initié',
            ]),
        ]);

        $this->actingAs($client)
            ->post(route('missions.close', $mission))
            ->assertRedirect(route('missions.show', $mission));

        $this->assertSame(PaymentStatus::Released, $payment->fresh()->status);
        $this->assertSame(MissionStatus::Closed, $mission->fresh()->status);
    }
}
