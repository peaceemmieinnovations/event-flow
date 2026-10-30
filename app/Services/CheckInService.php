<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckInService
{
    public const STATE_READY = 'ready';

    public const STATE_CHECKED_IN = 'checked_in';

    public const STATE_ALREADY_CHECKED_IN = 'already_checked_in';

    public const STATE_WRONG_EVENT = 'wrong_event';

    public const STATE_INVALID = 'invalid';

    public const STATE_TICKET_CANCELLED = 'ticket_cancelled';

    public const STATE_REGISTRATION_CANCELLED = 'registration_cancelled';

    public const STATE_PAYMENT_PENDING = 'payment_pending';

    /**
     * Messages are shown to staff, so they stay short, unambiguous and never
     * leak database or gateway internals.
     *
     * @var array<string, string>
     */
    public const STATE_MESSAGES = [
        self::STATE_CHECKED_IN => 'Checked in',
        self::STATE_ALREADY_CHECKED_IN => 'Already checked in',
        self::STATE_WRONG_EVENT => 'Wrong event',
        self::STATE_INVALID => 'Invalid ticket',
        self::STATE_TICKET_CANCELLED => 'Ticket cancelled',
        self::STATE_REGISTRATION_CANCELLED => 'Registration cancelled',
        self::STATE_PAYMENT_PENDING => 'Payment not completed',
    ];

    /**
     * Validate a scanned or typed ticket code against an event and, when it is
     * good, check the attendee in.
     *
     * @return array{state: string, message: string, tone: string, ticket: ?Ticket, attendee: ?string, ticket_number: ?string, checked_in_at: ?string}
     */
    public function checkIn(Event $event, string $lookup): array
    {
        $lookup = trim($lookup);

        if ($lookup === '') {
            return $this->result(self::STATE_INVALID);
        }

        $ticket = $this->resolve($lookup);

        if (! $ticket) {
            return $this->result(self::STATE_INVALID);
        }

        if ($ticket->event_id !== $event->id) {
            return $this->result(self::STATE_WRONG_EVENT, $ticket);
        }

        if ($ticket->isCancelled()) {
            return $this->result(self::STATE_TICKET_CANCELLED, $ticket);
        }

        $registration = $ticket->relationLoaded('registration')
            ? $ticket->registration
            : $ticket->registration()->first();

        if ($registration?->isCancelled()) {
            return $this->result(self::STATE_REGISTRATION_CANCELLED, $ticket);
        }

        if ($registration?->isPending()) {
            return $this->result(self::STATE_PAYMENT_PENDING, $ticket);
        }

        if ($ticket->isCheckedIn()) {
            return $this->result(self::STATE_ALREADY_CHECKED_IN, $ticket);
        }

        $checkedIn = $this->markCheckedIn($ticket->id);

        return $checkedIn ?? $this->result(self::STATE_ALREADY_CHECKED_IN, $ticket);
    }

    /**
     * Look a ticket up without mutating it. Used for "verify before scanning".
     *
     * Mirrors the checks in {@see self::checkIn()} so staff are never told a ticket
     * is ready when checking it in would actually be refused.
     *
     * @return array{state: string, message: string, tone: string, ticket: ?Ticket, attendee: ?string, ticket_number: ?string, checked_in_at: ?string}
     */
    public function verify(Event $event, string $lookup): array
    {
        $lookup = trim($lookup);

        if ($lookup === '') {
            return $this->result(self::STATE_INVALID);
        }

        $ticket = $this->resolve($lookup);

        if (! $ticket) {
            return $this->result(self::STATE_INVALID);
        }

        if ($ticket->event_id !== $event->id) {
            return $this->result(self::STATE_WRONG_EVENT, $ticket);
        }

        if ($ticket->isCancelled()) {
            return $this->result(self::STATE_TICKET_CANCELLED, $ticket);
        }

        $registration = $ticket->registration;

        if ($registration?->isCancelled()) {
            return $this->result(self::STATE_REGISTRATION_CANCELLED, $ticket);
        }

        if ($registration?->isPending()) {
            return $this->result(self::STATE_PAYMENT_PENDING, $ticket);
        }

        if ($ticket->isCheckedIn()) {
            return $this->result(self::STATE_ALREADY_CHECKED_IN, $ticket);
        }

        return $this->result(self::STATE_READY, $ticket);
    }

    /**
     * Tickets accept either the secure QR token or the printed ticket number.
     */
    public function resolve(string $lookup): ?Ticket
    {
        $normalized = Str::upper(trim($lookup));

        return Ticket::query()
            ->with(['event:id,title', 'registration:id,event_id,first_name,last_name,email,status'])
            ->where(fn ($query) => $query
                ->where('token', trim($lookup))
                ->orWhereRaw('UPPER(ticket_number) = ?', [$normalized]))
            ->first();
    }

    /**
     * @return array{state: string, message: string, tone: string, ticket: ?Ticket, attendee: ?string, ticket_number: ?string, checked_in_at: ?string}|null
     */
    private function markCheckedIn(int $ticketId): ?array
    {
        $result = DB::transaction(function () use ($ticketId): ?array {
            $ticket = Ticket::query()->with('registration')->lockForUpdate()->find($ticketId);

            // Another device may have checked this ticket in between the read
            // and the write, so the locked row is the real source of truth.
            if (! $ticket || $ticket->status !== Event::TICKET_STATUS_VALID) {
                return null;
            }

            $ticket->update([
                'status' => Event::TICKET_STATUS_CHECKED_IN,
                'checked_in_at' => now(),
                'checked_in_by' => auth()->id(),
            ]);

            $ticket->registration?->update(['status' => Registration::STATUS_ATTENDED]);

            return $this->result(self::STATE_CHECKED_IN, $ticket->fresh('registration'));
        });

        return $result;
    }

    /**
     * @return array{state: string, message: string, tone: string, ticket: ?Ticket, attendee: ?string, ticket_number: ?string, checked_in_at: ?string}
     */
    private function result(string $state, ?Ticket $ticket = null): array
    {
        // A ticket from another event is reported without attendee details:
        // staff working this door have no business learning who is booked on a
        // different event. Every other state is same-event and needs the name so
        // staff can explain a cancellation or an unpaid registration.
        $isForeignTicket = $state === self::STATE_WRONG_EVENT;

        return [
            'state' => $state,
            'message' => self::STATE_MESSAGES[$state] ?? 'Ready to scan',
            'tone' => match ($state) {
                self::STATE_CHECKED_IN, self::STATE_READY => 'success',
                self::STATE_ALREADY_CHECKED_IN, self::STATE_PAYMENT_PENDING => 'warning',
                default => 'danger',
            },
            'ticket' => $ticket,
            'attendee' => $isForeignTicket ? null : $ticket?->registration?->attendeeName(),
            'ticket_number' => $ticket?->ticket_number,
            'checked_in_at' => $ticket?->checked_in_at?->format('g:i A'),
        ];
    }
}
