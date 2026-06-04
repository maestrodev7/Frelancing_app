<?php

namespace Tests\Feature;

use App\Domain\Missions\Enums\DisputeStatus;
use App\Domain\Missions\Enums\MissionStatus;
use App\Domain\Missions\Enums\ProposalStatus;
use App\Domain\Users\Enums\AccountStatus;
use App\Domain\Users\Enums\UserRole;
use App\Models\Country;
use App\Models\Dispute;
use App\Models\Mission;
use App\Models\MissionReview;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private const STAFF_TOKEN = 'test-provisioning-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.staff_provisioning.token' => self::STAFF_TOKEN]);
    }

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

    private function secretaryUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::Secretary,
            'country_id' => $this->countryId(),
        ]);
    }

    /**
     * @return array{0: Mission, 1: User}
     */
    private function inProgressMission(): array
    {
        $client = $this->clientUser();
        $freelancer = $this->freelancerUser();

        $mission = Mission::create([
            'user_id' => $client->id,
            'title' => 'Mission en cours',
            'description' => 'Description test.',
            'type' => 'fixed',
            'currency' => 'EUR',
            'status' => MissionStatus::InProgress,
            'moderation_status' => 'approved',
        ]);

        $proposal = Proposal::create([
            'mission_id' => $mission->id,
            'user_id' => $freelancer->id,
            'cover_letter' => 'Proposition acceptée',
            'pricing_type' => 'fixed',
            'amount_fixed' => 500,
            'delivery_days' => 7,
            'status' => ProposalStatus::Accepted,
        ]);

        $mission->update(['accepted_proposal_id' => $proposal->id]);

        return [$mission->fresh(), $freelancer];
    }

    public function test_staff_can_be_created_via_api_with_token(): void
    {
        $response = $this->postJson('/api/staff', [
            'name' => 'Admin Test',
            'email' => 'admin-staff@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'admin',
        ], [
            'Authorization' => 'Bearer '.self::STAFF_TOKEN,
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseHas('users', [
            'email' => 'admin-staff@example.com',
            'role' => UserRole::Admin->value,
            'status' => AccountStatus::Active->value,
        ]);
    }

    public function test_staff_api_rejects_invalid_token(): void
    {
        $this->postJson('/api/staff', [
            'name' => 'Secretary',
            'email' => 'sec@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'secretary',
        ], [
            'Authorization' => 'Bearer wrong-token',
        ])->assertForbidden();
    }

    public function test_staff_logs_in_via_same_login_route(): void
    {
        $staff = User::factory()->create([
            'email' => 'secretary@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Secretary,
            'status' => AccountStatus::Active,
            'country_id' => $this->countryId(),
        ]);

        $this->post('/login', [
            'email' => 'secretary@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($staff);
    }

    public function test_client_can_open_dispute_and_staff_can_resolve(): void
    {
        [$mission, $freelancer] = $this->inProgressMission();
        $client = User::find($mission->user_id);
        $staff = $this->secretaryUser();

        $this->actingAs($client)
            ->post("/missions/{$mission->id}/disputes", [
                'reason' => 'Retard important sur les livrables.',
            ])
            ->assertRedirect(route('missions.show', $mission));

        $dispute = Dispute::query()->where('mission_id', $mission->id)->first();
        $this->assertNotNull($dispute);
        $this->assertSame(DisputeStatus::Open, $dispute->status);
        $this->assertSame(MissionStatus::Disputed, $mission->fresh()->status);

        $this->actingAs($freelancer)
            ->get(route('missions.show', $mission))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('dispute.status', 'open'));

        $this->actingAs($staff)
            ->patch("/admin/disputes/{$dispute->id}/resolve", [
                'resolution_notes' => 'Reprise après médiation.',
                'resolution_outcome' => 'resume_mission',
            ])
            ->assertRedirect(route('admin.disputes.show', $dispute));

        $this->assertSame(DisputeStatus::Resolved, $dispute->fresh()->status);
        $this->assertSame(MissionStatus::InProgress, $mission->fresh()->status);
    }

    public function test_mutual_reviews_after_mission_closed(): void
    {
        [$mission, $freelancer] = $this->inProgressMission();
        $client = User::find($mission->user_id);

        $mission->update(['status' => MissionStatus::Closed]);

        $this->actingAs($client)
            ->post("/missions/{$mission->id}/reviews", [
                'rating' => 5,
                'comment' => 'Excellent travail.',
            ])
            ->assertRedirect(route('missions.show', $mission));

        $this->actingAs($freelancer)
            ->post("/missions/{$mission->id}/reviews", [
                'rating' => 4,
                'comment' => 'Bon client.',
            ])
            ->assertRedirect(route('missions.show', $mission));

        $this->assertSame(2, MissionReview::query()->where('mission_id', $mission->id)->count());
    }

    public function test_staff_can_list_and_suspend_users_with_confirmation(): void
    {
        $staff = $this->secretaryUser();
        $target = $this->freelancerUser();

        $this->actingAs($staff)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Index')
                ->has('users'));

        $this->actingAs($staff)
            ->patch("/admin/users/{$target->id}/status", [
                'status' => AccountStatus::Suspended->value,
            ])
            ->assertSessionHasErrors('confirm');

        $this->actingAs($staff)
            ->patch("/admin/users/{$target->id}/status", [
                'status' => AccountStatus::Suspended->value,
                'confirm' => true,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(AccountStatus::Suspended, $target->fresh()->status);
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::Client,
            'status' => AccountStatus::Suspended,
            'country_id' => $this->countryId(),
        ]);

        $this->post('/login', [
            'email' => 'suspended@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
