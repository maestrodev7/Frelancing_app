<?php

namespace Tests\Feature;

use App\Domain\Contact\Enums\ContactMessageStatus;
use App\Domain\Users\Enums\UserRole;
use App\Models\ContactMessage;
use App\Models\Country;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Landing/Welcome'));
    }

    public function test_visitor_can_submit_contact_form(): void
    {
        $this->post('/contact', [
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'phone' => '677000000',
            'subject' => 'Question inscription',
            'message' => 'Comment créer un compte freelance ?',
        ])->assertRedirect(route('contact'));

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'jean@example.com',
            'status' => ContactMessageStatus::New->value,
        ]);
    }

    public function test_staff_can_view_contact_messages(): void
    {
        ContactMessage::create([
            'name' => 'Marie',
            'email' => 'marie@example.com',
            'subject' => 'Aide',
            'message' => 'Besoin d assistance',
            'status' => ContactMessageStatus::New,
        ]);

        $staff = User::factory()->create([
            'role' => UserRole::Secretary,
            'country_id' => Country::query()->firstOrCreate(
                ['code' => 'CM'],
                ['name' => 'Cameroun'],
            )->id,
        ]);

        $this->actingAs($staff)
            ->get('/admin/contacts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Contacts/Index')
                ->has('messages', 1));
    }
}
