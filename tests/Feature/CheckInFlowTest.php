<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CheckInService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInFlowTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * A published event with one registration and its ticket.
     *
     * @return array{0: Event, 1: Registration, 2: Ticket}
     */
    private function scenario(string $registrationState = Registration::STATUS_CONFIRMED): array
    {
        $event = Event::factory()->published()->create();
        $registration = Registration::factory()
            ->forEvent($event)
            ->state(fn (): array => ['status' => $registrationState])
            ->create();
        $ticket = Ticket::factory()->forRegistration($registration)->create();

        return [$event, $registration, $ticket];
    }

    public function test_guests_and_attendees_cannot_reach_the_check_in_screen(): void
    {
        $event = Event::factory()->published()->create();

        $this->get(route('admin.tickets.checkin', $event))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->attendee()->create())
            ->get(route('admin.tickets.checkin', $event))
            ->assertForbidden();
    }

    public function test_the_door_screen_is_available_to_staff(): void
    {
        [$event] = $this->scenario();

        $this->actingAs($this->staff())
            ->get(route('admin.tickets.checkin', $event))
            ->assertOk()
            ->assertSee($event->title);
    }

    public function test_a_draft_event_cannot_be_worked_as_a_door(): void
    {
        $event = Event::factory()->draft()->create();

        $this->actingAs($this->staff())
            ->get(route('admin.tickets.checkin', $event))
            ->assertNotFound();
    }

    public function test_a_valid_qr_token_checks_the_attendee_in(): void
    {
        $staff = $this->staff();
        [$event, $registration, $ticket] = $this->scenario();

        $this->actingAs($staff)
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => $ticket->token])
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_CHECKED_IN)
            ->assertJsonPath('attendee', $registration->attendeeName());

        $ticket->refresh();
        $this->assertSame(Event::TICKET_STATUS_CHECKED_IN, $ticket->status);
        $this->assertNotNull($ticket->checked_in_at);
        $this->assertSame($staff->id, $ticket->checked_in_by);
        $this->assertSame(Registration::STATUS_ATTENDED, $registration->fresh()->status);
    }

    public function test_the_printed_ticket_number_also_works_and_ignores_case(): void
    {
        [$event, , $ticket] = $this->scenario();

        $this->actingAs($this->staff())
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => strtolower($ticket->ticket_number)])
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_CHECKED_IN);
    }

    public function test_a_second_scan_is_reported_as_already_checked_in(): void
    {
        $first = $this->staff();
        [$event, , $ticket] = $this->scenario();

        $this->actingAs($first)
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => $ticket->token])
            ->assertJsonPath('state', CheckInService::STATE_CHECKED_IN);

        $original = $ticket->fresh();

        // A second device must not be able to check the same ticket in again.
        $second = User::factory()->organizer()->create();
        $this->actingAs($second)
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => $ticket->token])
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_ALREADY_CHECKED_IN);

        $ticket->refresh();
        $this->assertSame(Event::TICKET_STATUS_CHECKED_IN, $ticket->status);
        $this->assertTrue($original->checked_in_at->equalTo($ticket->checked_in_at), 'The original check-in time must not be overwritten.');
        $this->assertSame($first->id, $ticket->checked_in_by, 'The original staff member must remain the one credited.');
    }

    public function test_a_ticket_from_another_event_is_refused(): void
    {
        [$otherEvent] = $this->scenario();
        [, , $ticket] = $this->scenario();

        $this->actingAs($this->staff())
            ->postJson(route('admin.tickets.checkin.process', $otherEvent), ['code' => $ticket->token])
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_WRONG_EVENT);

        $this->assertSame(Event::TICKET_STATUS_VALID, $ticket->fresh()->status);
    }

    public function test_an_unknown_code_is_reported_as_invalid(): void
    {
        [$event] = $this->scenario();

        $this->actingAs($this->staff())
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => 'not-a-real-code'])
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_INVALID);
    }

    public function test_a_blank_code_is_rejected_by_validation(): void
    {
        [$event] = $this->scenario();

        $this->actingAs($this->staff())
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => ''])
            ->assertStatus(422);
    }

    public function test_a_cancelled_ticket_cannot_be_redeemed(): void
    {
        [$event, , $ticket] = $this->scenario();
        $ticket->update(['status' => Event::TICKET_STATUS_CANCELLED]);

        $this->actingAs($this->staff())
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => $ticket->token])
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_TICKET_CANCELLED);

        $this->assertNull($ticket->fresh()->checked_in_at);
    }

    public function test_a_cancelled_registration_cannot_be_redeemed(): void
    {
        [$event, $registration, $ticket] = $this->scenario();
        $registration->update(['status' => Registration::STATUS_CANCELLED]);

        $this->actingAs($this->staff())
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => $ticket->token])
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_REGISTRATION_CANCELLED);

        $this->assertSame(Event::TICKET_STATUS_VALID, $ticket->fresh()->status);
    }

    public function test_an_unpaid_registration_cannot_pass_the_door(): void
    {
        [$event, , $ticket] = $this->scenario(Registration::STATUS_PENDING);

        $this->actingAs($this->staff())
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => $ticket->token])
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_PAYMENT_PENDING);

        $this->assertSame(Event::TICKET_STATUS_VALID, $ticket->fresh()->status);
    }

    public function test_verifying_a_code_never_mutates_a_ticket(): void
    {
        [$event, , $ticket] = $this->scenario();

        $this->actingAs($this->staff())
            ->getJson(route('admin.tickets.checkin.verify', $event).'?code='.$ticket->token)
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_READY);

        $ticket->refresh();
        $this->assertSame(Event::TICKET_STATUS_VALID, $ticket->status);
        $this->assertNull($ticket->checked_in_at);
    }

    public function test_verifying_an_unpaid_registration_is_never_reported_as_ready(): void
    {
        [$event, , $ticket] = $this->scenario(Registration::STATUS_PENDING);

        // The verify screen must agree with check-in, otherwise staff let
        // someone through who will then be refused at the desk.
        $this->actingAs($this->staff())
            ->getJson(route('admin.tickets.checkin.verify', $event).'?code='.$ticket->token)
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_PAYMENT_PENDING);
    }

    public function test_the_door_never_returns_another_events_ticket_details(): void
    {
        [$otherEvent] = $this->scenario();
        [, , $ticket] = $this->scenario();

        $this->actingAs($this->staff())
            ->postJson(route('admin.tickets.checkin.process', $otherEvent), ['code' => $ticket->token])
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_WRONG_EVENT)
            ->assertJsonPath('attendee', null);
    }

    public function test_a_super_admin_may_work_any_door(): void
    {
        [$event, , $ticket] = $this->scenario();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => $ticket->token])
            ->assertOk()
            ->assertJsonPath('state', CheckInService::STATE_CHECKED_IN);
    }

    public function test_the_event_picker_lists_every_workable_event(): void
    {
        [$event] = $this->scenario();

        $this->actingAs($this->staff())
            ->get(route('admin.tickets.checkin.index'))
            ->assertOk()
            ->assertSee($event->title)
            ->assertSee(route('admin.tickets.checkin', $event), escape: false);
    }

    public function test_the_door_screen_reports_live_counts(): void
    {
        [$event, , $ticket] = $this->scenario();
        // A second issued ticket that nobody has arrived with yet.
        Ticket::factory()->forRegistration(Registration::factory()->forEvent($event)->create())->create();

        $this->actingAs($this->staff())
            ->postJson(route('admin.tickets.checkin.process', $event), ['code' => $ticket->token])
            ->assertOk();

        $this->actingAs($this->staff())
            ->get(route('admin.tickets.checkin', $event))
            ->assertOk()
            ->assertSee('data-checkin-count>1<', escape: false)
            ->assertSee('<dd>2</dd>', escape: false);
    }
}
