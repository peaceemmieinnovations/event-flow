<?php

namespace Database\Seeders;

use App\Models\CommunicationLog;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Realistic sample data for local development and demonstrations.
 *
 * Everything is created through the models directly rather than through the
 * registration, payment and communication services, so seeding never queues an
 * email, never calls Paystack and never depends on a gateway key being set.
 *
 * The seeder is idempotent: running it twice leaves the same set of events and
 * accounts, so it is safe to run after a `migrate:fresh` or a `--seed`.
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private const PASSWORD = 'password';

    /**
     * @var array<int, array{title: string, city: string, category: string, type: string, price: int, offsetDays: int}>
     */
    private const EVENTS = [
        ['title' => 'Lagos Product Design Summit', 'city' => 'Lagos', 'category' => 'business', 'type' => 'paid', 'price' => 45_000, 'offsetDays' => 9],
        ['title' => 'Afrobeats Rooftop Sessions', 'city' => 'Lagos', 'category' => 'music', 'type' => 'paid', 'price' => 25_000, 'offsetDays' => 12],
        ['title' => 'Calabar Jollof Cook-Off', 'city' => 'Calabar', 'category' => 'food-drink', 'type' => 'free', 'price' => 0, 'offsetDays' => 5],
        ['title' => 'Nairobi Founders Breakfast', 'city' => 'Nairobi', 'category' => 'business', 'type' => 'free', 'price' => 0, 'offsetDays' => 7],
        ['title' => 'Abuja Art & Clay Workshop', 'city' => 'Abuja', 'category' => 'arts-culture', 'type' => 'paid', 'price' => 18_000, 'offsetDays' => 16],
        ['title' => 'Ibadan Community Run', 'city' => 'Ibadan', 'category' => 'community', 'type' => 'free', 'price' => 0, 'offsetDays' => 3],
        ['title' => 'Lagos Photography Walk', 'city' => 'Lagos', 'category' => 'arts-culture', 'type' => 'paid', 'price' => 15_000, 'offsetDays' => -4],
        ['title' => 'Port Harcourt Startup Clinic', 'city' => 'Port Harcourt', 'category' => 'wellness', 'type' => 'paid', 'price' => 30_000, 'offsetDays' => 21],
    ];

    public function run(): void
    {
        $users = $this->users();
        $events = $this->events();

        $this->attendees($events, $users);

        $this->command->info('Demo accounts:');
        foreach ($users as $label => $user) {
            $this->command->info(sprintf('  %-11s %-26s %s', $label, $user->email, self::PASSWORD));
        }

        $this->command->info(sprintf(
            'Seeded %d events and %d registrations.',
            count($events),
            Registration::count(),
        ));
    }

    /**
     * @return array{organizer: User, admin: User, attendee: User}
     */
    private function users(): array
    {
        return [
            'organizer' => $this->account('organizer@eventflow.test', 'Demo Organizer', User::ROLE_ORGANIZER),
            'admin' => $this->account('admin@eventflow.test', 'Demo Administrator', User::ROLE_ADMIN),
            'attendee' => $this->account('attendee@eventflow.test', 'Demo Attendee', User::ROLE_ATTENDEE),
        ];
    }

    private function account(string $email, string $name, string $role): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'role' => $role,
            'email_verified_at' => now(),
            'password' => $user->exists ? $user->password : Hash::make(self::PASSWORD),
            'remember_token' => $user->remember_token ?? str()->random(10),
        ])->save();

        return $user;
    }

    /**
     * @return array<int, Event>
     */
    private function events(): array
    {
        $events = [];

        foreach (self::EVENTS as $index => $definition) {
            $slug = str($definition['title'])->slug()->toString();
            $existing = Event::query()->where('slug', $slug)->first();

            if ($existing) {
                $events[] = $existing;

                continue;
            }

            $eventDate = now()->addDays($definition['offsetDays'])->setTime(16, 0);
            $isPast = $definition['offsetDays'] < 0;

            $events[] = Event::factory()->create([
                'title' => $definition['title'],
                'slug' => $slug,
                'description' => $this->description($definition),
                'category' => $definition['category'],
                'city' => $definition['city'],
                'location' => $definition['city'].' Convention Centre',
                'address' => ($index + 1).' Marina Road',
                'organizer_name' => 'EventFlow Demo Team',
                'organizer_email' => 'hello@eventflow.test',
                'organizer_phone' => '+2348000000000',
                'event_type' => $definition['type'],
                'price_amount' => $definition['price'],
                'currency' => 'NGN',
                'capacity' => 120 + ($index * 40),
                'status' => Event::STATUS_PUBLISHED,
                'published_at' => now()->subDays(3),
                'event_date' => $eventDate,
                'start_time' => '16:00',
                'end_time' => '20:00',
                'registration_deadline' => $eventDate->copy()->subDay()->setTime(23, 59),
            ]);
        }

        // One draft the team has not published yet, and one cancelled event so
        // the admin screens show every lifecycle state.
        $draft = Event::query()->where('slug', 'a-yet-unpublished-meetup')->first()
            ?? Event::factory()->draft()->create([
                'title' => 'A Yet Unpublished Meetup',
                'slug' => 'a-yet-unpublished-meetup',
                'category' => 'business',
                'city' => 'Lagos',
                'event_date' => now()->addDays(28)->setTime(18, 0),
            ]);
        $events[] = $draft;

        $cancelled = Event::query()->where('slug', 'harbour-side-sound-system')->first()
            ?? Event::factory()->cancelled()->create([
                'title' => 'Harbour Side Sound System',
                'slug' => 'harbour-side-sound-system',
                'category' => 'music',
                'city' => 'Lagos',
                'event_date' => now()->addDays(10)->setTime(19, 0),
            ]);
        $events[] = $cancelled;

        return $events;
    }

    /**
     * @param  array<string, string|int>  $definition
     */
    private function description(array $definition): string
    {
        return sprintf(
            'A %s event in %s bringing the community together for an afternoon of connection, good company and a shared love of what we do. Expect a welcoming crowd, plenty of time to meet people and an experience worth writing home about.',
            str_replace('_', ' ', (string) $definition['category']),
            $definition['city'],
        );
    }

    /**
     * Populate the published events with a believable mix of attendees, tickets,
     * payments and attendance so every admin screen has something to show.
     *
     * @param  array<int, Event>  $events
     * @param  array{organizer: User, admin: User, attendee: User}  $users
     */
    private function attendees(array $events, array $users): void
    {
        foreach ($events as $eventIndex => $event) {
            if (in_array($event->status, [Event::STATUS_DRAFT], true)) {
                continue;
            }

            if (Registration::query()->where('event_id', $event->id)->exists()) {
                continue;
            }

            $isCancelledEvent = $event->status === Event::STATUS_CANCELLED;
            $isPast = $event->event_date->isPast();
            $isFree = $event->event_type === Event::TYPE_FREE;

            // 6 confirmed, 2 awaiting payment, 1 cancelled for published events.
            $plan = $isCancelledEvent
                ? [Registration::STATUS_CANCELLED, Registration::STATUS_CANCELLED]
                : [
                    ...array_fill(0, 6, Registration::STATUS_CONFIRMED),
                    ...array_fill(0, 2, Registration::STATUS_PENDING),
                    Registration::STATUS_CANCELLED,
                ];

            foreach ($plan as $registrationIndex => $status) {
                $attended = $isPast && $status === Registration::STATUS_CONFIRMED;

                $registration = Registration::factory()->create([
                    'event_id' => $event->id,
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'email' => sprintf('guest%d%d@example.test', $eventIndex, $registrationIndex),
                    'status' => $attended ? Registration::STATUS_ATTENDED : $status,
                    'registered_at' => now()->subDays(min($registrationIndex + 1, 6)),
                ]);

                if ($status !== Registration::STATUS_CONFIRMED) {
                    // Awaiting-payment rows only make sense for a paid event.
                    if ($status === Registration::STATUS_PENDING && ! $isFree && ! $isCancelledEvent) {
                        Payment::factory()->forRegistration($registration)->create([
                            'event_id' => $event->id,
                            'amount' => $event->price_amount,
                            'currency' => $event->currency,
                            'status' => Payment::STATUS_PENDING,
                            'paid_at' => null,
                        ]);
                    }

                    continue;
                }

                $checkedIn = $attended && ! $isCancelledEvent;

                $ticket = Ticket::factory()->create([
                    'registration_id' => $registration->id,
                    'event_id' => $event->id,
                    'status' => $isCancelledEvent
                        ? Event::TICKET_STATUS_CANCELLED
                        : ($checkedIn ? Event::TICKET_STATUS_CHECKED_IN : Event::TICKET_STATUS_VALID),
                    'checked_in_at' => $checkedIn ? now()->subHours(3) : null,
                    'checked_in_by' => $checkedIn ? $users['admin']->id : null,
                ]);

                // Free events charge nothing, so there is no settlement to record.
                if (! $isFree && ! $isCancelledEvent) {
                    Payment::factory()->forRegistration($registration)->create([
                        'event_id' => $event->id,
                        'amount' => $event->price_amount,
                        'currency' => $event->currency,
                        'status' => Payment::STATUS_SUCCESSFUL,
                        'paid_at' => now()->subDays(2),
                    ]);
                }

                $this->history($registration, $event, $ticket, $checkedIn);
            }
        }
    }

    /**
     * Write the communication history directly so seeding queues no mail.
     */
    private function history(Registration $registration, Event $event, Ticket $ticket, bool $checkedIn): void
    {
        foreach ([
            [CommunicationLog::TYPE_REGISTRATION_CONFIRMATION, 'Registration confirmed', 'You are registered. Keep this reference handy: '.$registration->reference],
            [CommunicationLog::TYPE_TICKET_ISSUED, 'Your ticket is ready', 'Your EventFlow ticket is attached below. Show the QR code at the door.'],
            [CommunicationLog::TYPE_REMINDER_24H, 'Starting tomorrow · '.$event->title, 'Your event starts tomorrow. Your ticket and QR code are in your EventFlow wallet.'],
        ] as [$type, $subject, $message]) {
            CommunicationLog::query()->firstOrCreate(
                ['dedupe_key' => 'r'.$registration->id.':'.$type],
                [
                    'event_id' => $event->id,
                    'registration_id' => $registration->id,
                    'recipient_email' => $registration->email,
                    'type' => $type,
                    'subject' => $subject,
                    'message' => $message,
                    'channel' => 'mail',
                    'recipient_count' => 1,
                    'status' => CommunicationLog::STATUS_SENT,
                ],
            );
        }

        if ($checkedIn) {
            CommunicationLog::query()->firstOrCreate(
                ['dedupe_key' => 'r'.$registration->id.':'.CommunicationLog::TYPE_REMINDER_1H],
                [
                    'event_id' => $event->id,
                    'registration_id' => $registration->id,
                    'recipient_email' => $registration->email,
                    'type' => CommunicationLog::TYPE_REMINDER_1H,
                    'subject' => 'Starting in about an hour · '.$event->title,
                    'message' => 'Your event starts in about an hour. Have your ticket QR code ready for the door.',
                    'channel' => 'mail',
                    'recipient_count' => 1,
                    'status' => CommunicationLog::STATUS_SENT,
                ],
            );
        }

        unset($ticket);
    }
}
