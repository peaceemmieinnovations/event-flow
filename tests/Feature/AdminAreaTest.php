<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAreaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every screen a staff member can open. Items may be parametrised by a
     * registration that exists for a published event.
     *
     * @return array<string, array{0: string, 1: callable(): array<int, mixed>}>
     */
    private function screens(): array
    {
        return [
            'dashboard' => ['admin.dashboard', fn (): array => []],
            'analytics' => ['admin.analytics.index', fn (): array => []],
            'events index' => ['admin.events.index', fn (): array => []],
            'events create' => ['admin.events.create', fn (): array => []],
            'registrations' => ['admin.registrations.index', fn (): array => []],
            'tickets' => ['admin.tickets.index', fn (): array => []],
            'check-in picker' => ['admin.tickets.checkin.index', fn (): array => []],
            'payments' => ['admin.payments.index', fn (): array => []],
            'communications' => ['admin.communications.index', fn (): array => []],
            'compose' => ['admin.communications.compose', fn (): array => []],
        ];
    }

    public function test_guests_are_redirected_to_login_from_every_admin_screen(): void
    {
        foreach ($this->screens() as [$route, $params]) {
            $this->get(route($route, $params()))
                ->assertRedirect(route('login'), "Guests must not reach {$route}.");
        }
    }

    public function test_attendees_are_forbidden_from_every_admin_screen(): void
    {
        $attendee = User::factory()->attendee()->create();

        foreach ($this->screens() as [$route, $params]) {
            $this->actingAs($attendee)
                ->get(route($route, $params()))
                ->assertForbidden("Attendees must not reach {$route}.");
        }
    }

    public function test_organizers_and_admins_can_open_every_admin_screen(): void
    {
        [$event, $registration] = $this->fixtures();

        foreach ([User::factory()->organizer(), User::factory()->admin()] as $factory) {
            $staff = $factory->create();

            foreach ($this->screens() as [$route, $params]) {
                $this->actingAs($staff)
                    ->get(route($route, $params()))
                    ->assertOk("{$staff->role} must be able to open {$route}.");
            }
        }

        unset($event, $registration);
    }

    public function test_a_super_admin_is_accepted_by_the_staff_middleware(): void
    {
        // The middleware names admin and organizer only; super_admin must be
        // admitted as well, or the top-level role cannot administer anything.
        foreach ($this->screens() as [$route, $params]) {
            $this->actingAs(User::factory()->superAdmin()->create())
                ->get(route($route, $params()))
                ->assertOk("Super admins must be able to open {$route}.");
        }
    }

    public function test_event_scoped_admin_pages_are_open_to_staff(): void
    {
        [$event, $registration, $payment] = $this->fixtures();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.events.edit', $event))->assertOk();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.events.analytics', $event))->assertOk();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.registrations.show', $registration))->assertOk();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.payments.show', $payment))->assertOk();
    }

    public function test_event_scoped_admin_pages_still_refuse_attendees(): void
    {
        [$event, $registration, $payment] = $this->fixtures();
        $attendee = User::factory()->attendee()->create();

        $this->actingAs($attendee)->get(route('admin.events.edit', $event))->assertForbidden();
        $this->actingAs($attendee)->get(route('admin.events.analytics', $event))->assertForbidden();
        $this->actingAs($attendee)->get(route('admin.registrations.show', $registration))->assertForbidden();
        $this->actingAs($attendee)->get(route('admin.payments.show', $payment))->assertForbidden();
        $this->actingAs($attendee)
            ->patch(route('admin.registrations.status', $registration), ['status' => 'cancelled'])
            ->assertForbidden();
    }

    /**
     * @return array{0: Event, 1: Registration, 2: Payment}
     */
    private function fixtures(): array
    {
        $event = Event::factory()->published()->create();
        $registration = Registration::factory()->forEvent($event)->confirmed()->create();
        $payment = Payment::factory()->forRegistration($registration)->create();

        return [$event, $registration, $payment];
    }
}
