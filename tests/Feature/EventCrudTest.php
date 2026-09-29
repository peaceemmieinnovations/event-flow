<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->create(['role' => User::ROLE_ADMIN]); }
    private function data(array $overrides = []): array { return array_merge(['title'=>'Lagos Design Week','description'=>'A gathering.','event_date'=>'2026-12-10','start_time'=>'10:00','location'=>'Civic Centre','event_type'=>'free','status'=>'draft'], $overrides); }

    public function test_admin_can_create_update_and_delete_an_event(): void
    {
        $this->actingAs($this->admin())->post(route('admin.events.store'), $this->data())->assertRedirect(route('admin.events.index'));
        $event = Event::sole();
        $this->assertSame('lagos-design-week', $event->slug);
        $this->actingAs($this->admin())->put(route('admin.events.update', $event), $this->data(['title'=>'Updated Event','slug'=>'updated-event']))->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseHas('events', ['slug'=>'updated-event']);
        $this->actingAs($this->admin())->delete(route('admin.events.destroy', $event))->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseCount('events', 0);
    }

    public function test_invalid_events_and_duplicate_slugs_are_rejected(): void
    {
        Event::create($this->data(['slug'=>'taken']));
        $this->actingAs($this->admin())->from(route('admin.events.create'))->post(route('admin.events.store'), $this->data(['slug'=>'taken','end_time'=>'09:00']))->assertSessionHasErrors(['slug','end_time']);
    }

    public function test_only_published_events_are_public(): void
    {
        $draft = Event::create($this->data(['slug'=>'draft']));
        $published = Event::create($this->data(['title'=>'Published Event','slug'=>'published','status'=>'published']));
        $this->get(route('events.index'))->assertDontSee($draft->title)->assertSee($published->title);
        $this->get(route('events.show', $draft))->assertNotFound();
        $this->get(route('events.show', $published))->assertOk();
    }
}
