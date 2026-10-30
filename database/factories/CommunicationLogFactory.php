<?php

namespace Database\Factories;

use App\Models\CommunicationLog;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationLog>
 */
class CommunicationLogFactory extends Factory
{
    protected $model = CommunicationLog::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'event_id' => null,
            'dedupe_key' => null,
            'recipient_email' => fake()->safeEmail(),
            'type' => CommunicationLog::TYPE_REGISTRATION_CONFIRMATION,
            'subject' => fake()->sentence(4),
            'message' => fake()->paragraph(2),
            'channel' => 'mail',
            'status' => CommunicationLog::STATUS_QUEUED,
            'recipient_count' => 1,
            'sent_at' => null,
            'error_message' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => CommunicationLog::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function failed(string $message = 'SMTP unavailable'): static
    {
        return $this->state(fn (): array => [
            'status' => CommunicationLog::STATUS_FAILED,
            'error_message' => $message,
        ]);
    }
}
