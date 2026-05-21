<?php

namespace Tests\Feature\Auth;

use App\Domain\Users\Enums\AccountStatus;
use App\Domain\Users\Enums\UserRole;
use App\Models\Country;
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
        $country = Country::create([
            'name' => 'Cameroun',
            'code' => 'CM',
        ]);

        $response = $this->post('/register', [
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
        $country = Country::create([
            'name' => 'France',
            'code' => 'FR',
        ]);

        $response = $this->postJson('/register', [
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
}
