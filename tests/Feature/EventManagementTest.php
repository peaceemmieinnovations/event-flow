<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_event_management(): void
    {
        $this->get('/admin/events')->assertRedirect(route('login'));
    }

    public function test_attendees_cannot_access_event_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ATTENDEE]))
            ->get('/admin/events')
            ->assertForbidden();
    }

    public function test_admins_and_super_admins_can_view_the_empty_event_management_page(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get('/admin/events')
                ->assertOk()
                ->assertSee('No events yet');
        }
    }

    public function test_event_management_lists_real_events(): void
    {
        $event = Event::create([
            'title' => 'Design Assembly',
            'description' => 'A gathering for curious makers.',
            'location' => 'Lagos',
            'event_date' => '2026-10-15 10:00:00',
            'slug' => 'design-assembly',
        ]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->get('/admin/events')
            ->assertOk()
            ->assertSee($event->title)
            ->assertSee($event->location)
            ->assertSee('10:00 AM')
            ->assertSee('Draft');
    }
}
