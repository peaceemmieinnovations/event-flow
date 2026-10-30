<?php

namespace App\Jobs;

use App\Models\CommunicationLog;
use App\Notifications\EventLifecycleNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delivers a single queued communication and records the outcome.
 *
 * The job never throws for mail errors: the communication log is updated and
 * the failure is traced, so a bad address can never break an event workflow.
 */
class SendCommunication implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 60;

    public function __construct(public int $communicationLogId) {}

    public function handle(): void
    {
        $log = CommunicationLog::query()->with('registration.event')->find($this->communicationLogId);

        if (! $log || $log->status === CommunicationLog::STATUS_SENT) {
            return;
        }

        $registration = $log->registration;

        if (! $registration || ! $registration->event) {
            $this->failLog($log, 'Registration or event is no longer available.');

            return;
        }

        try {
            $registration->notify(new EventLifecycleNotification(
                $registration,
                $log->type,
                $log->subject,
                $log->message,
            ));
        } catch (Throwable $exception) {
            $this->failLog($log, $exception->getMessage());

            return;
        }

        $log->update([
            'status' => CommunicationLog::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    private function failLog(CommunicationLog $log, string $message): void
    {
        Log::warning('EventFlow communication could not be delivered.', [
            'communication_log_id' => $log->id,
            'type' => $log->type,
            'exception' => $message,
        ]);

        $log->update([
            'status' => CommunicationLog::STATUS_FAILED,
            'error_message' => mb_substr($message, 0, 500),
        ]);
    }
}
