<?php

namespace Tests\Feature;

use App\Domain\Users\Enums\UserRole;
use App\Models\Country;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $initialCountry = Country::query()->firstOrCreate(
            ['code' => 'CM'],
            ['name' => 'Cameroun'],
        );
        $newCountry = Country::query()->firstOrCreate(
            ['code' => 'FR'],
            ['name' => 'France'],
        );
        $user = User::factory()->create([
            'phone' => '+237600000000',
            'country_id' => $initialCountry->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '+33123456789',
                'country_id' => $newCountry->id,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('+33123456789', $user->phone);
        $this->assertSame($newCountry->id, $user->country_id);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $country = Country::query()->firstOrCreate(
            ['code' => 'CM'],
            ['name' => 'Cameroun'],
        );
        $user = User::factory()->create([
            'country_id' => $country->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'phone' => $user->phone,
                'country_id' => $user->country_id,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_freelancer_profile_can_be_updated(): void
    {
        $country = Country::query()->firstOrCreate(
            ['code' => 'CM'],
            ['name' => 'Cameroun'],
        );
        $user = User::factory()->create([
            'role' => UserRole::Freelancer,
            'country_id' => $country->id,
        ]);

        $user->freelancerProfile()->create([
            'title' => 'Freelance',
            'bio' => 'Bio initiale',
            'currency' => 'XAF',
            'experience_years' => 1,
            'availability_status' => 'available',
            'timezone' => 'Africa/Douala',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile/freelancer', [
                'title' => 'Lead Laravel Freelancer',
                'bio' => 'Je construis des applications web performantes pour des missions produit.',
                'hourly_rate_default' => 50,
                'currency' => 'EUR',
                'experience_years' => 7,
                'availability_status' => 'limited',
                'timezone' => 'Europe/Paris',
                'portfolio_url' => 'https://portfolio.example.com',
                'linkedin_url' => 'https://linkedin.com/in/freelancer-example',
                'skills' => [
                    ['name' => 'Laravel', 'level' => 5],
                    ['name' => 'React', 'level' => 4],
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $profileId = $user->freelancerProfile()->value('id');
        $laravelSkillId = Skill::query()->where('name', 'Laravel')->value('id');

        $this->assertDatabaseHas('freelancer_profiles', [
            'user_id' => $user->id,
            'title' => 'Lead Laravel Freelancer',
            'currency' => 'EUR',
            'experience_years' => 7,
            'availability_status' => 'limited',
            'timezone' => 'Europe/Paris',
        ]);
        $this->assertDatabaseHas('freelancer_skills', [
            'freelancer_profile_id' => $profileId,
            'skill_id' => $laravelSkillId,
            'level' => 5,
        ]);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
