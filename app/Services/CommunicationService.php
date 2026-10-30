<?php

namespace App\Services;

use App\Jobs\SendCommunication;
use App\Models\CommunicationLog;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single entry point for every outbound EventFlow communication.
 *
 * Design rules:
 *  - Delivery is always queued, never performed inline.
 *  - A log row is written first, so a repeated call is a no-op (idempotent).
 *  - Mail failures are recorded but never bubble into the caller: a failed
 *    email must never break a registration, ticket or payment.
 */
class CommunicationService
{
    /**
     * Record a communication and queue its delivery.
     */
    public function dispatch(
        ?Registration $registration,
        string $type,
        string $subject,
        string $message,
        bool $onceOnly = true,
        bool $force = false,
    ): ?CommunicationLog {
        $attributes = [
            'event_id' => $registration?->event_id,
            'registration_id' => $registration?->id,
            'recipient_email' => $registration === null ? '' : $registration->email,
            'type' => $type,
            // Bulk sends deliberately leave the key null so the unique index
            // does not block the same person receiving several broadcasts.
            'dedupe_key' => $onceOnly && $registration ? 'r'.$registration->id.':'.$type : null,
            'subject' => $subject,
            'message' => $message,
            'channel' => 'mail',
            'recipient_count' => 1,
            'status' => CommunicationLog::STATUS_QUEUED,
            'error_message' => null,
        ];

        try {
            if ($onceOnly) {
                $log = CommunicationLog::firstOrCreate(
                    ['dedupe_key' => $attributes['dedupe_key']],
                    $attributes,
                );

                // A resend re-queues the same row instead of creating a second
                // one, so the history of what was sent stays accurate.
                if (! $log->wasRecentlyCreated && $force) {
                    $log->update($attributes);
                }
            } else {
                $log = CommunicationLog::create($attributes);
            }
        } catch (Throwable $exception) {
            Log::warning('Unable to record communication log.', [
                'type' => $type,
                'registration_id' => $registration?->id,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        if ($log->wasRecentlyCreated || $force) {
            SendCommunication::dispatch($log->id);
        }

        return $log;
    }

    public function registrationConfirmed(Registration $registration, bool $force = false): void
    {
        $event = $registration->event;
        $reference = $registration->isPending() ? 'still awaiting payment' : 'confirmed';

        $this->dispatch(
            $registration,
            CommunicationLog::TYPE_REGISTRATION_CONFIRMATION,
            'Registration '.$reference.' · '.$event->title,
            $registration->isPending()
                ? 'Thanks for registering. Your place is held while we confirm your payment.'
                : 'You are registered. Keep this reference handy: '.$registration->reference,
            force: $force,
        );
    }

    public function ticketIssued(Registration $registration, bool $force = false): void
    {
        $event = $registration->event;

        $this->dispatch(
            $registration,
            CommunicationLog::TYPE_TICKET_ISSUED,
            'Your ticket is ready · '.$event->title,
            'Your EventFlow ticket is attached below. Show the QR code at the door.',
            force: $force,
        );
    }

    /**
     * Re-send the whole confirmation pack. Used by the attendee confirmation
     * page when a guest reports a missing email.
     */
    public function resend(Registration $registration): void
    {
        $registration->loadMissing('event', 'ticket');

        if ($registration->isPending()) {
            $this->registrationConfirmed($registration, force: true);

            return;
        }

        $this->registrationConfirmed($registration, force: true);

        if ($registration->ticket) {
            $this->ticketIssued($registration, force: true);
        }
    }

    public function paymentReceived(Registration $registration, Payment $payment): void
    {
        $event = $registration->event;

        $this->dispatch(
            $registration,
            CommunicationLog::TYPE_PAYMENT_RECEIPT,
            'Payment received · '.$event->title,
            'We received your payment of '.$payment->formattedAmount().'. Reference '.$payment->reference.'.',
        );
    }

    public function registrationStatusChanged(Registration $registration, string $previousStatus): void
    {
        $event = $registration->event;

        $this->dispatch(
            $registration,
            CommunicationLog::TYPE_REGISTRATION_STATUS,
            'Registration '.ucfirst($registration->status).' · '.$event->title,
            'Your registration status changed from '.str_replace('_', ' ', $previousStatus).' to '.str_replace('_', ' ', $registration->status).'.',
        );
    }

    public function eventUpdated(Event $event, string $message): void
    {
        foreach ($this->activeRegistrations($event) as $registration) {
            $this->dispatch(
                $registration,
                CommunicationLog::TYPE_EVENT_UPDATED,
                'Update · '.$event->title,
                $message,
            );
        }
    }

    public function eventCancelled(Event $event): void
    {
        foreach ($this->activeRegistrations($event) as $registration) {
            $this->dispatch(
                $registration,
                CommunicationLog::TYPE_EVENT_CANCELLED,
                'Cancelled · '.$event->title,
                'This event has been cancelled. You will not be charged, and your ticket is no longer valid.',
            );
        }
    }

    /**
     * Admin broadcast to a chosen audience.
     *
     * @return int recipient count
     */
    public function broadcast(Event $event, string $audience, string $subject, string $message): int
    {
        $registrations = $this->audienceRegistrations($event, $audience);

        if ($registrations->isEmpty()) {
            return 0;
        }

        foreach ($registrations as $registration) {
            $this->dispatch(
                $registration,
                CommunicationLog::TYPE_ADMIN_BULK,
                $subject,
                $message,
                false,
            );
        }

        return $registrations->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Registration>
     */
    public function audienceRegistrations(Event $event, string $audience)
    {
        return match ($audience) {
            'confirmed' => $event->registrations()->where('status', Registration::STATUS_CONFIRMED)->get(),
            'pending' => $event->registrations()->where('status', Registration::STATUS_PENDING)->get(),
            'attended' => $event->registrations()->where('status', Registration::STATUS_ATTENDED)->get(),
            'checked_in' => $event->registrations()->whereHas('ticket', fn ($query) => $query->where('status', Event::TICKET_STATUS_CHECKED_IN))->get(),
            default => $this->activeRegistrations($event),
        };
    }

    /**
     * @return array<string, string>
     */
    public function audiences(): array
    {
        return [
            'all' => 'All active attendees',
            'confirmed' => 'Confirmed only',
            'pending' => 'Awaiting payment',
            'checked_in' => 'Checked in',
            'attended' => 'Marked attended',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Registration>
     */
    private function activeRegistrations(Event $event)
    {
        return $event->registrations()->whereIn('status', Event::ACTIVE_REGISTRATION_STATUSES)->get();
    }
}
