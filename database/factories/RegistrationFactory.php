<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => null,
            'reference' => 'EF-'.Str::upper(Str::random(8)),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+23480########'),
            'organization' => fake()->company(),
            'notes' => null,
            'status' => Registration::STATUS_CONFIRMED,
            'registered_at' => now(),
        ];
    }

    public function forEvent(Event|int $event): static
    {
        return $this->state(fn (): array => [
            'event_id' => $event instanceof Event ? $event->id : $event,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => ['status' => Registration::STATUS_PENDING]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => ['status' => Registration::STATUS_CONFIRMED]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => Registration::STATUS_CANCELLED]);
    }

    public function attended(): static
    {
        return $this->state(fn (): array => ['status' => Registration::STATUS_ATTENDED]);
    }

    public function forUser(?User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user?->id]);
    }
}
