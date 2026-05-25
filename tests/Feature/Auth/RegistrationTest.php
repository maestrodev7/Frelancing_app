<?php

namespace Tests\Feature\Auth;

use App\Domain\Users\Enums\AccountStatus;
use App\Domain\Users\Enums\UserRole;
use App\Models\Country;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $country = Country::query()->firstOrCreate(
            ['code' => 'CM'],
            ['name' => 'Cameroun'],
        );

        $response = $this->post('/register', [
            'account_type' => UserRole::Client->value,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+237699000000',
            'billing_address' => '123 Rue Client',
            'country_id' => $country->id,
            'timezone' => 'Africa/Douala',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $user = auth()->user();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Client, $user->role);
        $this->assertSame(AccountStatus::Active, $user->status);
        $this->assertSame('+237699000000', $user->phone);
        $this->assertSame($country->id, $user->country_id);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'country_id' => $country->id,
        ]);
        $this->assertDatabaseHas('client_profiles', [
            'user_id' => $user->id,
            'billing_address' => '123 Rue Client',
            'timezone' => 'Africa/Douala',
            'total_spent' => 0,
            'jobs_posted_count' => 0,
        ]);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_client_accounts_can_be_created_from_json_request(): void
    {
        $country = Country::query()->firstOrCreate(
            ['code' => 'FR'],
            ['name' => 'France'],
        );

        $response = $this->postJson('/register', [
            'account_type' => UserRole::Client->value,
            'name' => 'Client User',
            'email' => 'client@example.com',
            'phone' => '+33123456789',
            'billing_address' => '10 Avenue Test',
            'country_id' => $country->id,
            'timezone' => 'Europe/Paris',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Client User')
            ->assertJsonPath('user.email', 'client@example.com')
            ->assertJsonPath('user.phone', '+33123456789')
            ->assertJsonPath('user.avatar_url', null)
            ->assertJsonPath('user.role', UserRole::Client->value)
            ->assertJsonPath('user.status', AccountStatus::Active->value)
            ->assertJsonPath('user.country.id', $country->id)
            ->assertJsonPath('user.country.name', 'France')
            ->assertJsonPath('user.client_profile.billing_address', '10 Avenue Test')
            ->assertJsonPath('user.client_profile.timezone', 'Europe/Paris')
            ->assertJsonPath('user.client_profile.jobs_posted_count', 0);

        $this->assertDatabaseHas('users', [
            'email' => 'client@example.com',
            'phone' => '+33123456789',
            'country_id' => $country->id,
            'avatar_url' => null,
            'role' => UserRole::Client->value,
            'status' => AccountStatus::Active->value,
        ]);
        $this->assertDatabaseHas('client_profiles', [
            'billing_address' => '10 Avenue Test',
            'timezone' => 'Europe/Paris',
        ]);
    }

    public function test_freelancer_accounts_can_be_created_from_json_request(): void
    {
        $country = Country::query()->firstOrCreate(
            ['code' => 'CM'],
            ['name' => 'Cameroun'],
        );

        $response = $this->postJson('/register', [
            'account_type' => UserRole::Freelancer->value,
            'name' => 'Freelancer User',
            'email' => 'freelancer@example.com',
            'phone' => '+237680000000',
            'country_id' => $country->id,
            'timezone' => 'Africa/Douala',
            'title' => 'Developpeur Laravel & React',
            'bio' => 'Je cree des applications web robustes pour des missions produit.',
            'hourly_rate_default' => 35,
            'currency' => 'EUR',
            'experience_years' => 6,
            'availability_status' => 'available',
            'portfolio_url' => 'https://portfolio.example.com',
            'linkedin_url' => 'https://linkedin.com/in/freelancer-example',
            'skills' => [
                ['name' => 'Laravel', 'level' => 5],
                ['name' => 'React', 'level' => 4],
            ],
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'freelancer@example.com')->first();

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Freelancer User')
            ->assertJsonPath('user.role', UserRole::Freelancer->value)
            ->assertJsonPath('user.country.code', 'CM')
            ->assertJsonPath('user.client_profile', null)
            ->assertJsonPath('user.freelancer_profile.title', 'Developpeur Laravel & React')
            ->assertJsonPath('user.freelancer_profile.currency', 'EUR')
            ->assertJsonPath('user.freelancer_profile.availability_status', 'available')
            ->assertJsonPath('user.freelancer_profile.skills.0.name', 'Laravel')
            ->assertJsonPath('user.freelancer_profile.skills.0.level', 5);

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Freelancer, $user->role);
        $this->assertDatabaseHas('freelancer_profiles', [
            'user_id' => $user->id,
            'title' => 'Developpeur Laravel & React',
            'currency' => 'EUR',
            'experience_years' => 6,
            'availability_status' => 'available',
            'timezone' => 'Africa/Douala',
        ]);

        $laravelSkillId = Skill::query()->where('name', 'Laravel')->value('id');
        $reactSkillId = Skill::query()->where('name', 'React')->value('id');
        $freelancerProfileId = $user->freelancerProfile()->value('id');

        $this->assertDatabaseHas('freelancer_skills', [
            'freelancer_profile_id' => $freelancerProfileId,
            'skill_id' => $laravelSkillId,
            'level' => 5,
        ]);
        $this->assertDatabaseHas('freelancer_skills', [
            'freelancer_profile_id' => $freelancerProfileId,
            'skill_id' => $reactSkillId,
            'level' => 4,
        ]);
    }
}
