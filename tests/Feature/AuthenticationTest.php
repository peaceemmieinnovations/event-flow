<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_and_auth_forms_are_available(): void
    {
        $event = Event::create([
            'title' => 'Test event', 'description' => 'A test event.', 'location' => 'Lagos',
            'event_date' => now()->addWeek(), 'image' => null, 'slug' => 'test-event', 'status' => 'published',
        ]);

        $this->get('/')->assertOk();
        $this->get('/events')->assertOk();
        $this->get(route('events.show', $event))->assertOk();
        $this->get('/events/999999')->assertNotFound();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_registration_creates_a_hashed_attendee_and_redirects_to_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Attendee',
            'email' => 'attendee@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $response->assertRedirect('/dashboard');
        $user = User::where('email', 'attendee@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_ATTENDEE, $user->role);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_registration_and_invalid_login_are_rejected(): void
    {
        User::factory()->create(['email' => 'member@example.com']);

        $this->from('/register')->post('/register', [
            'name' => 'Duplicate', 'email' => 'member@example.com',
            'password' => 'secure-password', 'password_confirmation' => 'secure-password',
        ])->assertRedirect('/register')->assertSessionHasErrors('email');

        $this->from('/login')->post('/login', [
            'email' => 'member@example.com', 'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secure-password')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secure-password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_owner_is_created_once_as_super_admin_and_role_checks_are_strict(): void
    {
        $this->seed(SuperAdminSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $owner = User::where('email', SuperAdminSeeder::OWNER_EMAIL)->sole();
        $this->assertSame(User::ROLE_SUPER_ADMIN, $owner->role);
        $this->assertTrue(Gate::forUser($owner)->allows('super-admin'));
        $this->assertTrue($owner->hasAnyRole([User::ROLE_ADMIN]));
        $this->assertFalse(Gate::forUser(User::factory()->create(['role' => User::ROLE_ADMIN]))->allows('super-admin'));
        $this->assertFalse(Gate::forUser(User::factory()->create(['role' => User::ROLE_ORGANIZER]))->allows('super-admin'));
        $this->assertFalse(Gate::forUser(User::factory()->create(['role' => User::ROLE_ATTENDEE]))->allows('super-admin'));
    }
}
