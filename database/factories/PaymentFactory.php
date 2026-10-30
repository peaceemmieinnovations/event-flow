<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'event_id' => fn (array $attributes): int => (int) Registration::query()
                ->whereKey($attributes['registration_id'])
                ->value('event_id'),
            'reference' => 'PAY-'.Str::upper(Str::random(12)),
            'gateway' => 'paystack',
            'gateway_transaction_id' => null,
            'amount' => 25_000,
            'currency' => 'NGN',
            'status' => Payment::STATUS_PENDING,
            'paid_at' => null,
            'metadata' => null,
        ];
    }

    public function forRegistration(Registration $registration): static
    {
        return $this->state(fn (): array => [
            'registration_id' => $registration->id,
            'event_id' => $registration->event_id,
            'amount' => $registration->event->price_amount,
            'currency' => $registration->event->currency,
        ]);
    }

    public function successful(): static
    {
        return $this->state(fn (): array => [
            'status' => Payment::STATUS_SUCCESSFUL,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => ['status' => Payment::STATUS_FAILED]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (): array => ['status' => Payment::STATUS_ABANDONED]);
    }
}
