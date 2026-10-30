<?php

namespace Tests\Feature;

use App\Jobs\SendCommunication;
use App\Models\CommunicationLog;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Services\CommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommunicationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tomorrow_event_gets_the_24_hour_reminder(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->happeningInHours(24)->create();
        Registration::factory()->forEvent($event)->confirmed()->create();

        $this->artisan('eventflow:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('communication_logs', 1);
        $this->assertDatabaseHas('communication_logs', [
            'type' => CommunicationLog::TYPE_REMINDER_24H,
            'status' => CommunicationLog::STATUS_QUEUED,
        ]);
        Queue::assertPushed(SendCommunication::class, 1);
    }

    public function test_running_the_reminder_command_twice_never_sends_a_second_email(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->happeningInHours(24)->create();
        Registration::factory()->forEvent($event)->confirmed()->create();

        $this->artisan('eventflow:send-reminders')->assertSuccessful();
        $this->artisan('eventflow:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('communication_logs', 1);
        // The job is written once because the second run finds the existing row.
        Queue::assertPushed(SendCommunication::class, 1);
    }

    public function test_the_two_reminder_windows_are_separate_messages(): void
    {
        Queue::fake();

        $tomorrow = Event::factory()->published()->happeningInHours(24)->create(['title' => 'Tomorrow Event']);
        $soon = Event::factory()->published()->happeningInHours(1)->create(['title' => 'Soon Event']);

        Registration::factory()->forEvent($tomorrow)->confirmed()->create();
        Registration::factory()->forEvent($soon)->confirmed()->create();

        $this->artisan('eventflow:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('communication_logs', 2);
        $this->assertDatabaseHas('communication_logs', ['type' => CommunicationLog::TYPE_REMINDER_24H, 'subject' => 'Starting tomorrow · Tomorrow Event']);
        $this->assertDatabaseHas('communication_logs', ['type' => CommunicationLog::TYPE_REMINDER_1H, 'subject' => 'Starting in about an hour · Soon Event']);
    }

    public function test_an_event_that_is_neither_tomorrow_nor_an_hour_away_gets_nothing(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->happeningInHours(6)->create();
        Registration::factory()->forEvent($event)->confirmed()->create();

        $this->artisan('eventflow:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('communication_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_draft_cancelled_and_past_events_never_trigger_reminders(): void
    {
        Queue::fake();

        Registration::factory()->forEvent(Event::factory()->draft()->happeningInHours(24)->create())->confirmed()->create();
        Registration::factory()->forEvent(Event::factory()->cancelled()->happeningInHours(24)->create())->confirmed()->create();
        Registration::factory()->forEvent(Event::factory()->published()->create([
            'event_date' => now()->subDay(),
            'registration_deadline' => now()->subDays(2),
        ]))->confirmed()->create();

        $this->artisan('eventflow:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('communication_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_only_pending_and_confirmed_registrations_are_reminded(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->happeningInHours(24)->create();
        Registration::factory()->forEvent($event)->confirmed()->create();
        Registration::factory()->forEvent($event)->pending()->create();

        $this->artisan('eventflow:send-reminders')->assertSuccessful();
        $this->assertDatabaseCount('communication_logs', 2);
    }

    public function test_cancelled_and_attended_registrations_are_never_reminded(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->happeningInHours(24)->create();
        Registration::factory()->forEvent($event)->cancelled()->create();
        Registration::factory()->forEvent($event)->attended()->create();

        $this->artisan('eventflow:send-reminders')->assertSuccessful();

        $this->assertDatabaseCount('communication_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_an_admin_broadcast_reaches_every_active_attendee_once(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->create();
        Registration::factory()->forEvent($event)->confirmed()->create();
        Registration::factory()->forEvent($event)->confirmed()->create();
        Registration::factory()->forEvent($event)->pending()->create();
        Registration::factory()->forEvent($event)->cancelled()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.communications.store'), [
                'event_id' => $event->id,
                'audience' => 'all',
                'subject' => 'Doors open at noon',
                'message' => 'Please arrive before noon on the day.',
            ])
            ->assertRedirect(route('admin.communications.index'));

        // Cancelled attendees are excluded from every broadcast.
        $this->assertDatabaseCount('communication_logs', 3);
        $this->assertDatabaseHas('communication_logs', [
            'type' => CommunicationLog::TYPE_ADMIN_BULK,
            'subject' => 'Doors open at noon',
        ]);
        Queue::assertPushed(SendCommunication::class, 3);
    }

    public function test_repeating_the_same_broadcast_creates_a_separate_delivery(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->create();
        Registration::factory()->forEvent($event)->confirmed()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.communications.store'), [
                'event_id' => $event->id,
                'audience' => 'confirmed',
                'subject' => 'First notice',
                'message' => 'Doors open at noon.',
            ])->assertRedirect(route('admin.communications.index'));

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.communications.store'), [
                'event_id' => $event->id,
                'audience' => 'confirmed',
                'subject' => 'Second notice',
                'message' => 'A quick follow-up.',
            ])->assertRedirect(route('admin.communications.index'));

        // Bulk sends are deliberate repeats, so they must not deduplicate.
        $this->assertDatabaseCount('communication_logs', 2);
        Queue::assertPushed(SendCommunication::class, 2);
    }

    public function test_broadcasting_to_an_audience_with_nobody_in_it_queues_nothing(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->create();
        Registration::factory()->forEvent($event)->cancelled()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.communications.store'), [
                'event_id' => $event->id,
                'audience' => 'confirmed',
                'subject' => 'Nothing to send',
                'message' => 'Nobody should receive this.',
            ])
            ->assertRedirect(route('admin.communications.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('communication_logs', 0);
        Queue::assertNothingPushed();
    }

    public function test_the_broadcast_form_requires_every_field(): void
    {
        $event = Event::factory()->published()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.communications.store'), [
                'event_id' => $event->id,
                'audience' => '',
                'subject' => '',
                'message' => '',
            ])
            ->assertSessionHasErrors(['audience', 'subject', 'message']);

        $this->assertDatabaseCount('communication_logs', 0);
    }

    public function test_resending_a_confirmation_reuses_the_same_log_row(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->create();
        $registration = Registration::factory()->forEvent($event)->confirmed()->create();

        $communications = app(CommunicationService::class);
        $communications->registrationConfirmed($registration);
        $communications->registrationConfirmed($registration);

        $this->assertDatabaseCount('communication_logs', 1);
        Queue::assertPushed(SendCommunication::class, 1);

        // An explicit resend is allowed to re-queue the same row.
        $communications->resend($registration);

        $this->assertDatabaseCount('communication_logs', 1);
        Queue::assertPushed(SendCommunication::class, 2);
    }

    public function test_a_registration_confirmation_and_its_ticket_are_separate_logs(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->create();
        $registration = Registration::factory()->forEvent($event)->confirmed()->create();

        $communications = app(CommunicationService::class);
        $communications->registrationConfirmed($registration);
        $communications->ticketIssued($registration);

        $this->assertDatabaseCount('communication_logs', 2);

        $communications->ticketIssued($registration);
        $this->assertDatabaseCount('communication_logs', 2);
    }

    public function test_cancelling_an_event_notifies_every_active_attendee_exactly_once(): void
    {
        Queue::fake();

        $event = Event::factory()->published()->create();
        Registration::factory()->forEvent($event)->confirmed()->create();
        Registration::factory()->forEvent($event)->pending()->create();
        Registration::factory()->forEvent($event)->cancelled()->create();

        $communications = app(CommunicationService::class);
        $communications->eventCancelled($event);
        $communications->eventCancelled($event);

        $this->assertDatabaseCount('communication_logs', 2);
        $this->assertDatabaseHas('communication_logs', ['type' => CommunicationLog::TYPE_EVENT_CANCELLED]);

        Queue::assertPushed(SendCommunication::class, 2);
    }
}
