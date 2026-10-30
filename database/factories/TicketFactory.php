<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'event_id' => fn (array $attributes): int => (int) Registration::query()
                ->whereKey($attributes['registration_id'])
                ->value('event_id'),
            'ticket_number' => 'EF-'.Str::upper(Str::random(10)),
            'token' => Str::random(64),
            'ticket_type' => 'general',
            'status' => Event::TICKET_STATUS_VALID,
            'checked_in_at' => null,
            'checked_in_by' => null,
        ];
    }

    public function checkedIn(): static
    {
        return $this->state(fn (): array => [
            'status' => Event::TICKET_STATUS_CHECKED_IN,
            'checked_in_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => Event::TICKET_STATUS_CANCELLED]);
    }

    public function forRegistration(Registration $registration): static
    {
        return $this->state(fn (): array => [
            'registration_id' => $registration->id,
            'event_id' => $registration->event_id,
        ]);
    }
}
