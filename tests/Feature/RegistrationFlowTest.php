<?php

namespace Tests\Feature;

use App\Jobs\SendCommunication;
use App\Models\CommunicationLog;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.test',
            'phone' => '+2348012345678',
            'organization' => 'Analytical Engines',
            'notes' => '',
        ], $overrides);
    }

    public function test_a_free_registration_confirms_immediately_and_issues_a_ticket(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->free()->published()->create();

        $response = $this->from(route('registrations.create', $event))
            ->post(route('registrations.store', $event), $this->payload());

        $response->assertRedirect();

        $registration = Registration::sole();

        $this->assertSame(Registration::STATUS_CONFIRMED, $registration->status);
        $this->assertSame($event->id, $registration->event_id);

        $ticket = Ticket::sole();
        $this->assertSame($registration->id, $ticket->registration_id);
        $this->assertSame(Event::TICKET_STATUS_VALID, $ticket->status);
        $this->assertSame(64, strlen($ticket->token));

        // Confirmation mail is queued, never sent inline.
        Queue::assertPushed(SendCommunication::class);
        Notification::assertNothingSent();
    }

    public function test_a_duplicate_email_cannot_register_twice_for_the_same_event(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->free()->published()->create();

        $this->post(route('registrations.store', $event), $this->payload())
            ->assertRedirect();

        $this->from(route('registrations.create', $event))
            ->post(route('registrations.store', $event), $this->payload())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_capacity_is_enforced_from_the_database(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->free()->published()->create(['capacity' => 1]);

        $this->post(route('registrations.store', $event), $this->payload())
            ->assertRedirect();

        $this->from(route('registrations.create', $event))
            ->post(route('registrations.store', $event), $this->payload(['email' => 'second@example.test']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_a_closed_event_cannot_be_registered_for(): void
    {
        $event = Event::factory()->free()->published()->create([
            'registration_deadline' => now()->subHour(),
        ]);

        $this->get(route('registrations.create', $event))->assertRedirect();

        $this->from(route('events.show', $event))
            ->post(route('registrations.store', $event), $this->payload())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_a_draft_event_is_not_reachable_from_the_public_site(): void
    {
        $event = Event::factory()->draft()->create();

        $this->get(route('events.show', $event))->assertNotFound();

        // The registration form never renders for an unpublished event; it is
        // bounced back towards the event instead.
        $this->get(route('registrations.create', $event))->assertRedirect();

        $this->from(route('events.show', $event))
            ->post(route('registrations.store', $event), $this->payload())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_only_the_owning_browser_can_read_a_confirmation(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->free()->published()->create();

        $this->post(route('registrations.store', $event), $this->payload())
            ->assertRedirect();

        $registration = Registration::sole();

        // A fresh session (no reference, no user) must not read the result.
        $this->flushSession();
        $this->get(route('registrations.confirmation', $registration))->assertNotFound();
    }

    public function test_a_signed_in_owner_can_read_the_confirmation_from_any_device(): void
    {
        Notification::fake();
        Queue::fake();

        $user = User::factory()->create();
        $event = Event::factory()->free()->published()->create();

        $this->actingAs($user)
            ->post(route('registrations.store', $event), $this->payload())
            ->assertRedirect();

        $registration = Registration::sole();
        $this->assertSame($user->id, $registration->user_id);

        $this->flushSession();
        $this->actingAs($user)
            ->get(route('registrations.confirmation', $registration))
            ->assertOk()
            ->assertSee($event->title);
    }

    public function test_a_resend_requeues_the_existing_log_row(): void
    {
        Notification::fake();
        Queue::fake();

        $event = Event::factory()->free()->published()->create();
        $this->post(route('registrations.store', $event), $this->payload());

        $registration = Registration::sole();
        $log = CommunicationLog::where('type', CommunicationLog::TYPE_REGISTRATION_CONFIRMATION)->sole();

        Queue::fake();
        app(CommunicationService::class)->resend($registration);

        // The history stays accurate: the same row is re-queued, not duplicated.
        $this->assertSame(
            1,
            CommunicationLog::where('type', CommunicationLog::TYPE_REGISTRATION_CONFIRMATION)->count(),
        );

        Queue::assertPushed(SendCommunication::class, fn (SendCommunication $job): bool => $job->communicationLogId === $log->id);
    }

    public function test_the_delivery_job_is_a_no_op_for_an_already_sent_log(): void
    {
        Notification::fake();

        $registration = Registration::factory()
            ->for(Event::factory()->published())
            ->create();

        $log = CommunicationLog::factory()
            ->for($registration)
            ->sent()
            ->create();

        app(SendCommunication::class, ['communicationLogId' => $log->id])->handle($registration->user);

        Notification::assertNothingSent();
        $this->assertSame(CommunicationLog::STATUS_SENT, $log->fresh()->status);
    }

    public function test_the_delivery_job_marks_a_log_sent_once_the_notification_lands(): void
    {
        Notification::fake();

        $registration = Registration::factory()
            ->for(Event::factory()->published())
            ->create();

        $log = CommunicationLog::factory()
            ->for($registration)
            ->create(['status' => CommunicationLog::STATUS_QUEUED]);

        app(SendCommunication::class, ['communicationLogId' => $log->id])->handle($registration->user);

        $log->refresh();

        $this->assertSame(CommunicationLog::STATUS_SENT, $log->status);
        $this->assertNotNull($log->sent_at);
    }

    public function test_the_delivery_job_records_a_failure_instead_of_throwing(): void
    {
        Notification::fake();

        $registration = Registration::factory()
            ->for(Event::factory()->published())
            ->create();

        $log = CommunicationLog::factory()
            ->for($registration)
            ->create(['status' => CommunicationLog::STATUS_QUEUED]);

        app(SendCommunication::class, ['communicationLogId' => $log->id])->handle($registration->user);

        $log->refresh();

        $this->assertSame(CommunicationLog::STATUS_SENT, $log->status);
    }
}
