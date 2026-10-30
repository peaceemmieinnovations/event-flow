<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(3),
            'location' => fake()->city().' Community Centre',
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->word(),
            'country' => 'Nigeria',
            'category' => fake()->randomElement(Event::CATEGORIES),
            'organizer_name' => fake()->company(),
            'organizer_email' => fake()->companyEmail(),
            'organizer_phone' => fake()->numerify('+23480########'),
            'capacity' => 120,
            'event_type' => Event::TYPE_FREE,
            'price_amount' => 0,
            'currency' => 'NGN',
            'status' => Event::STATUS_PUBLISHED,
            'published_at' => now(),
            'event_date' => now()->addDays(14)->setTime(16, 0),
            'start_time' => '16:00',
            'end_time' => '19:00',
            'registration_deadline' => now()->addDays(12)->setTime(23, 59),
            'image' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => Event::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => Event::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => Event::STATUS_CANCELLED]);
    }

    public function free(): static
    {
        return $this->state(fn (): array => [
            'event_type' => Event::TYPE_FREE,
            'price_amount' => 0,
        ]);
    }

    public function paid(int $amount = 25_000): static
    {
        return $this->state(fn (): array => [
            'event_type' => Event::TYPE_PAID,
            'price_amount' => $amount,
            'currency' => 'NGN',
        ]);
    }

    /** The reminder command keys off the event date, so tests set it precisely. */
    public function happeningInHours(int $hours): static
    {
        // The time of day must be preserved: resetting it to midnight would
        // silently move the event into the past.
        return $this->state(fn (): array => [
            'event_date' => now()->addHours($hours),
            'registration_deadline' => now()->addHours($hours)->subHours(1),
        ]);
    }
}
