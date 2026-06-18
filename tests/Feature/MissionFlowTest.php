<?php

namespace Tests\Feature;

use App\Domain\Missions\Enums\MissionStatus;
use App\Domain\Missions\Enums\ProposalStatus;
use App\Domain\Users\Enums\UserRole;
use App\Models\Country;
use App\Models\Mission;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionFlowTest extends TestCase
{
    use RefreshDatabase;

    private function countryId(): int
    {
        return Country::query()->firstOrCreate(
            ['code' => 'CM'],
            ['name' => 'Cameroun'],
        )->id;
    }

    private function clientUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Client,
            'country_id' => $this->countryId(),
        ]);
    }

    private function freelancerUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Freelancer,
            'country_id' => $this->countryId(),
        ]);
    }

    public function test_client_can_create_a_mission(): void
    {
        $client = $this->clientUser();
        $client->clientProfile()->create([
            'billing_address' => 'Rue Test',
            'timezone' => 'Africa/Douala',
        ]);

        $response = $this->actingAs($client)->post('/missions', [
            'title' => 'Site vitrine',
            'description' => 'Création d\'un site vitrine responsive.',
            'type' => 'fixed',
            'budget_min' => 500,
            'budget_max' => 1500,
            'currency' => 'EUR',
            'deadline_at' => now()->addMonth()->toDateString(),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('missions', [
            'user_id' => $client->id,
            'title' => 'Site vitrine',
            'status' => MissionStatus::Open->value,
        ]);

        $this->assertSame(1, $client->clientProfile()->value('jobs_posted_count'));
    }

    public function test_freelancer_can_browse_open_missions_and_submit_proposal(): void
    {
        $client = $this->clientUser();
        $freelancer = $this->freelancerUser();

        $mission = Mission::create([
            'user_id' => $client->id,
            'title' => 'API Laravel',
            'description' => 'Développer une API REST.',
            'type' => 'hourly',
            'hourly_cap' => 50,
            'currency' => 'EUR',
            'status' => MissionStatus::Open,
            'moderation_status' => 'approved',
        ]);

        $this->actingAs($freelancer)
            ->get('/missions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Missions/FreelancerIndex')
                ->has('missions', 1));

        $response = $this->actingAs($freelancer)->post("/missions/{$mission->id}/proposals", [
            'cover_letter' => 'Je suis disponible pour démarrer rapidement.',
            'pricing_type' => 'hourly',
            'hourly_rate' => 45,
            'estimated_hours' => 40,
            'delivery_days' => 14,
        ]);

        $response->assertRedirect(route('missions.show', $mission));

        $this->assertDatabaseHas('proposals', [
            'mission_id' => $mission->id,
            'user_id' => $freelancer->id,
            'status' => ProposalStatus::Pending->value,
        ]);
    }

    public function test_client_can_accept_a_proposal_and_reject_others(): void
    {
        $client = $this->clientUser();
        $freelancerA = $this->freelancerUser();
        $freelancerB = $this->freelancerUser();

        $mission = Mission::create([
            'user_id' => $client->id,
            'title' => 'Design UI',
            'description' => 'Maquettes Figma.',
            'type' => 'fixed',
            'currency' => 'EUR',
            'status' => MissionStatus::Open,
            'moderation_status' => 'approved',
        ]);

        $accepted = Proposal::create([
            'mission_id' => $mission->id,
            'user_id' => $freelancerA->id,
            'cover_letter' => 'Proposition A',
            'pricing_type' => 'fixed',
            'amount_fixed' => 800,
            'delivery_days' => 10,
            'status' => ProposalStatus::Pending,
        ]);

        $rejected = Proposal::create([
            'mission_id' => $mission->id,
            'user_id' => $freelancerB->id,
            'cover_letter' => 'Proposition B',
            'pricing_type' => 'fixed',
            'amount_fixed' => 900,
            'delivery_days' => 12,
            'status' => ProposalStatus::Pending,
        ]);

        $this->actingAs($client)
            ->patch("/proposals/{$accepted->id}/accept")
            ->assertRedirect(route('proposals.payment.checkout', $accepted));

        $this->assertSame(ProposalStatus::Pending, $accepted->fresh()->status);
        $this->assertSame(MissionStatus::Open, $mission->fresh()->status);
    }

    public function test_client_can_reject_a_proposal(): void
    {
        $client = $this->clientUser();
        $freelancer = $this->freelancerUser();

        $mission = Mission::create([
            'user_id' => $client->id,
            'title' => 'Audit SEO',
            'description' => 'Audit complet.',
            'type' => 'fixed',
            'currency' => 'EUR',
            'status' => MissionStatus::Open,
            'moderation_status' => 'approved',
        ]);

        $proposal = Proposal::create([
            'mission_id' => $mission->id,
            'user_id' => $freelancer->id,
            'cover_letter' => 'Je propose un audit détaillé.',
            'pricing_type' => 'fixed',
            'amount_fixed' => 300,
            'delivery_days' => 5,
            'status' => ProposalStatus::Pending,
        ]);

        $this->actingAs($client)
            ->patch("/proposals/{$proposal->id}/reject")
            ->assertRedirect(route('missions.show', $mission));

        $this->assertSame(ProposalStatus::Rejected, $proposal->fresh()->status);
        $this->assertSame(MissionStatus::Open, $mission->fresh()->status);
    }

    public function test_accept_redirects_when_mission_awaiting_payment(): void
    {
        $client = $this->clientUser();
        $freelancer = $this->freelancerUser();

        $mission = Mission::create([
            'user_id' => $client->id,
            'title' => 'Mission attente',
            'description' => 'Test',
            'type' => 'fixed',
            'currency' => 'XAF',
            'status' => MissionStatus::AwaitingPayment,
            'moderation_status' => 'approved',
        ]);

        $proposal = Proposal::create([
            'mission_id' => $mission->id,
            'user_id' => $freelancer->id,
            'cover_letter' => 'Prop',
            'pricing_type' => 'fixed',
            'amount_fixed' => 500,
            'delivery_days' => 5,
            'status' => ProposalStatus::Pending,
        ]);

        $this->actingAs($client)
            ->patch("/proposals/{$proposal->id}/accept")
            ->assertRedirect(route('proposals.payment.checkout', $proposal));
    }

    public function test_client_can_update_open_mission(): void
    {
        $client = $this->clientUser();

        $mission = Mission::create([
            'user_id' => $client->id,
            'title' => 'Ancien titre',
            'description' => 'Ancienne description.',
            'type' => 'fixed',
            'currency' => 'XAF',
            'budget_min' => 100,
            'budget_max' => 500,
            'status' => MissionStatus::Open,
            'moderation_status' => 'approved',
        ]);

        $this->actingAs($client)
            ->patch(route('missions.update', $mission), [
                'title' => 'Nouveau titre',
                'description' => 'Nouvelle description détaillée.',
                'type' => 'fixed',
                'currency' => 'XAF',
                'budget_min' => 5000,
                'budget_max' => 10000,
            ])
            ->assertRedirect(route('missions.show', $mission));

        $mission->refresh();
        $this->assertSame('Nouveau titre', $mission->title);
        $this->assertSame(5000.0, (float) $mission->budget_min);
    }

    public function test_client_cannot_update_mission_in_progress(): void
    {
        $client = $this->clientUser();

        $mission = Mission::create([
            'user_id' => $client->id,
            'title' => 'Mission en cours',
            'description' => 'Test',
            'type' => 'fixed',
            'currency' => 'XAF',
            'status' => MissionStatus::InProgress,
            'moderation_status' => 'approved',
        ]);

        $this->actingAs($client)
            ->patch(route('missions.update', $mission), [
                'title' => 'Hack',
                'description' => 'Hack',
                'type' => 'fixed',
                'currency' => 'XAF',
            ])
            ->assertForbidden();
    }

    public function test_freelancer_cannot_apply_twice(): void
    {
        $client = $this->clientUser();
        $freelancer = $this->freelancerUser();

        $mission = Mission::create([
            'user_id' => $client->id,
            'title' => 'Mission unique',
            'description' => 'Test double candidature.',
            'type' => 'fixed',
            'currency' => 'EUR',
            'status' => MissionStatus::Open,
            'moderation_status' => 'approved',
        ]);

        Proposal::create([
            'mission_id' => $mission->id,
            'user_id' => $freelancer->id,
            'cover_letter' => 'Première proposition',
            'pricing_type' => 'fixed',
            'amount_fixed' => 100,
            'delivery_days' => 3,
            'status' => ProposalStatus::Pending,
        ]);

        $this->actingAs($freelancer)
            ->post("/missions/{$mission->id}/proposals", [
                'cover_letter' => 'Deuxième tentative',
                'pricing_type' => 'fixed',
                'amount_fixed' => 120,
                'delivery_days' => 4,
            ])
            ->assertStatus(422);
    }
}
