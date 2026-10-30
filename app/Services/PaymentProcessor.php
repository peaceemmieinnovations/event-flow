<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Registration;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentProcessor
{
    public function __construct(
        private readonly PaystackGateway $gateway,
        private readonly TicketIssuer $tickets,
    ) {}

    /**
     * Create (or reuse) a pending payment for a registration.
     *
     * The amount and currency always come from the event record, never from the
     * request, so a tampered form cannot change what is charged.
     */
    public function initiate(Registration $registration): Payment
    {
        $event = $registration->event;

        if ($event->isFree()) {
            throw new \InvalidArgumentException('Free events do not require a payment.');
        }

        $existing = $registration->payments()
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_SUCCESSFUL])
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return $registration->payments()->create([
            'event_id' => $event->id,
            'reference' => $this->gateway->newReference(),
            'gateway' => 'paystack',
            'amount' => (int) $event->price_amount,
            'currency' => $event->currency ?: 'NGN',
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    /**
     * Verify a payment against the gateway and, when it checks out, confirm the
     * registration and issue its ticket.
     *
     * Safe to call repeatedly: the operation is idempotent.
     *
     * The gateway distinguishes three outcomes that must not be conflated:
     *  - captured and matching the stored payment -> settle;
     *  - captured but the amount/currency/reference disagree -> refuse, and record
     *    the mismatch as a failure so it is never settled later;
     *  - simply not captured yet -> leave the payment alone. Marking an unpaid
     *    transaction as failed would destroy the attendee's chance to retry.
     *
     * @return array{ok: bool, reason: ?string, ticket: ?Ticket}
     */
    public function verify(Payment $payment): array
    {
        try {
            $data = $this->gateway->verify($payment->reference);
        } catch (Throwable $exception) {
            Log::warning('Payment verification request failed.', [
                'payment_reference' => $payment->reference,
                'exception' => $exception->getMessage(),
            ]);

            return ['ok' => false, 'reason' => 'unavailable', 'ticket' => null];
        }

        if (($data['status'] ?? null) !== 'success') {
            return ['ok' => false, 'reason' => 'unsettled', 'ticket' => null];
        }

        if (! $this->gateway->verificationMatches($payment, $data)) {
            $this->recordFailure($payment, $data);

            return ['ok' => false, 'reason' => 'mismatch', 'ticket' => null];
        }

        $ticket = $this->settle($payment, $data);

        return ['ok' => true, 'reason' => null, 'ticket' => $ticket];
    }

    /**
     * Apply a verified gateway result. Idempotent: replaying the same payload
     * never issues a second ticket or double-counts revenue.
     *
     * @param  array<string, mixed>  $data
     */
    public function settle(Payment $payment, array $data): Ticket
    {
        return DB::transaction(function () use ($payment, $data): Ticket {
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($locked->status === Payment::STATUS_SUCCESSFUL) {
                return $this->tickets->issue($locked->registration);
            }

            $locked->update([
                'status' => Payment::STATUS_SUCCESSFUL,
                'paid_at' => now(),
                'gateway_transaction_id' => isset($data['id']) ? (string) $data['id'] : $locked->gateway_transaction_id,
                'metadata' => $this->gateway->safeMetadata($data),
            ]);

            $registration = $locked->registration;
            $registration->update(['status' => Registration::STATUS_CONFIRMED]);

            return $this->tickets->issue($registration);
        });
    }

    /**
     * Mark a payment as failed. A failed payment never confirms a registration
     * and never produces a ticket, so the attendee can safely retry.
     *
     * @param  array<string, mixed>  $data
     */
    public function fail(Payment $payment, array $data = []): void
    {
        $this->recordFailure($payment, $data);
    }

    /**
     * Store what the gateway actually sent without changing the payment status.
     *
     * Used when a delivery references our reference but does not agree with it:
     * the charge is neither trusted nor discarded, it is kept for reconciliation.
     *
     * @param  array<string, mixed>  $data
     */
    public function flagForReconciliation(Payment $payment, array $data): void
    {
        Payment::query()
            ->whereKey($payment->id)
            ->update(['metadata' => $this->gateway->safeMetadata($data)]);
    }

    /**
     * Sweep pending payments that were never completed.
     */
    public function markAbandoned(Payment $payment): void
    {
        if ($payment->status === Payment::STATUS_PENDING) {
            $payment->update(['status' => Payment::STATUS_ABANDONED]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function recordFailure(Payment $payment, array $data): void
    {
        Payment::query()
            ->whereKey($payment->id)
            ->where('status', Payment::STATUS_PENDING)
            ->update([
                'status' => Payment::STATUS_FAILED,
                'metadata' => $this->gateway->safeMetadata($data),
            ]);
    }
}
