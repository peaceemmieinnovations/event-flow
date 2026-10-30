<?php

namespace App\Console\Commands;

use App\Models\CommunicationLog;
use App\Models\Event;
use App\Models\Registration;
use App\Services\CommunicationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Queue the 24-hour and 1-hour reminders for upcoming events.
 *
 * Idempotency lives in CommunicationService: every dispatch is keyed on the
 * registration and the reminder type, so running the command twice inside the
 * same window never sends a second email.
 */
class SendEventReminders extends Command
{
    protected $signature = 'eventflow:send-reminders';

    protected $description = 'Queue due EventFlow reminders';

    /**
     * How many minutes either side of the target time still count as "due", so a
     * scheduler that fires a few minutes late does not skip a whole window.
     */
    private const WINDOW_MINUTES = 5;

    /**
     * @var array<int, array{type: string, label: string, copy: string}>
     */
    private const REMINDERS = [
        24 => [
            'type' => CommunicationLog::TYPE_REMINDER_24H,
            'label' => 'tomorrow',
            'copy' => 'Your event starts tomorrow. Your ticket and QR code are in your EventFlow wallet.',
        ],
        1 => [
            'type' => CommunicationLog::TYPE_REMINDER_1H,
            'label' => 'in about an hour',
            'copy' => 'Your event starts in about an hour. Have your ticket QR code ready for the door.',
        ],
    ];

    public function handle(CommunicationService $communications): int
    {
        $queued = 0;

        foreach (self::REMINDERS as $hours => $reminder) {
            $registrations = $this->dueRegistrations($hours);
            $queuedForWindow = 0;

            foreach ($registrations as $registration) {
                $event = $registration->event;

                $log = $communications->dispatch(
                    $registration,
                    $reminder['type'],
                    'Starting '.$reminder['label'].' · '.$event->title,
                    $reminder['copy'],
                );

                if ($log?->wasRecentlyCreated) {
                    $queuedForWindow++;
                    $queued++;
                }
            }

            $this->components->info(sprintf(
                '%d-hour reminder window: %d registration(s) matched, %d queued.',
                $hours,
                $registrations->count(),
                $queuedForWindow,
            ));
        }

        $this->components->info($queued.' reminder(s) queued.');

        return self::SUCCESS;
    }

    /**
     * Active registrations for events that are due this reminder window.
     *
     * @return Collection<int, Registration>
     */
    private function dueRegistrations(int $hours)
    {
        $target = now()->addHours($hours);

        return Registration::query()
            ->with('event')
            ->whereIn('status', [Registration::STATUS_PENDING, Registration::STATUS_CONFIRMED])
            ->whereHas('event', fn ($query) => $query
                ->where('status', Event::STATUS_PUBLISHED)
                ->whereNotNull('event_date')
                ->whereBetween('event_date', [
                    $target->copy()->subMinutes(self::WINDOW_MINUTES),
                    $target->copy()->addMinutes(self::WINDOW_MINUTES),
                ]))
            ->get();
    }
}
