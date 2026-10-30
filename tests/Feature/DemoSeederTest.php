<?php

namespace Tests\Feature;

use App\Models\CommunicationLog;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_builds_a_complete_demo_dataset(): void
    {
        // Seeding must never queue mail or call the payment gateway.
        Queue::fake();

        $this->seed(DemoSeeder::class);

        $this->assertGreaterThan(8, Event::count(), 'Every lifecycle state should be represented.');
        $this->assertDatabaseHas('events', ['slug' => 'a-yet-unpublished-meetup', 'status' => Event::STATUS_DRAFT]);
        $this->assertDatabaseHas('events', ['slug' => 'harbour-side-sound-system', 'status' => Event::STATUS_CANCELLED]);
        $this->assertDatabaseHas('events', ['slug' => 'lagos-photography-walk']);

        $this->assertGreaterThan(0, Registration::where('status', Registration::STATUS_CONFIRMED)->count());
        $this->assertGreaterThan(0, Registration::where('status', Registration::STATUS_PENDING)->count());
        $this->assertGreaterThan(0, Registration::where('status', Registration::STATUS_CANCELLED)->count());
        $this->assertGreaterThan(0, Registration::where('status', Registration::STATUS_ATTENDED)->count());

        $this->assertGreaterThan(0, Ticket::where('status', Event::TICKET_STATUS_VALID)->count());
        $this->assertGreaterThan(0, Ticket::where('status', Event::TICKET_STATUS_CHECKED_IN)->count());

        $this->assertGreaterThan(0, Payment::where('status', Payment::STATUS_SUCCESSFUL)->count());
        $this->assertGreaterThan(0, Payment::where('status', Payment::STATUS_PENDING)->count());

        $this->assertGreaterThan(0, CommunicationLog::where('status', CommunicationLog::STATUS_SENT)->count());

        $this->assertTrue(User::where('email', 'organizer@eventflow.test')->exists());
        $this->assertTrue(User::where('email', 'admin@eventflow.test')->exists());
        $this->assertTrue(User::where('email', 'attendee@eventflow.test')->exists());

        // No mail should be pending from a seed run.
        Queue::assertNothingPushed();
    }

    public function test_the_seeder_only_uses_categories_the_platform_offers(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertEmpty(
            Event::query()->whereNotIn('category', Event::CATEGORIES)->pluck('category')->all(),
            'Demo events must use a category the public filter recognises.',
        );
    }

    public function test_every_paid_event_charges_its_own_listed_price(): void
    {
        $this->seed(DemoSeeder::class);

        $mismatches = Payment::query()
            ->with('event:id,event_type,price_amount,currency')
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->get()
            ->filter(function (Payment $payment): bool {
                return $payment->event?->event_type === 'paid'
                    && ($payment->amount !== $payment->event->price_amount
                        || $payment->currency !== $payment->event->currency);
            });

        $this->assertCount(0, $mismatches, 'Seeded payments must mirror the event price in minor units.');
    }

    public function test_seeding_twice_leaves_the_dataset_unchanged(): void
    {
        $this->seed(DemoSeeder::class);

        $counts = [
            'events' => Event::count(),
            'registrations' => Registration::count(),
            'tickets' => Ticket::count(),
            'payments' => Payment::count(),
            'users' => User::count(),
        ];

        $this->seed(DemoSeeder::class);

        $this->assertSame($counts['events'], Event::count());
        $this->assertSame($counts['registrations'], Registration::count());
        $this->assertSame($counts['tickets'], Ticket::count());
        $this->assertSame($counts['payments'], Payment::count());
        $this->assertSame($counts['users'], User::count());
    }
}
