<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketIssuer
{
    /**
     * Issue the single ticket that belongs to a registration.
     *
     * Idempotent by design: calling this repeatedly returns the same ticket, so
     * replayed payments or webhooks can never create duplicates.
     */
    public function issue(Registration $registration): Ticket
    {
        if ($existing = $registration->ticket()->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($registration): Ticket {
            if ($existing = $registration->ticket()->lockForUpdate()->first()) {
                return $existing;
            }

            return Ticket::create([
                'registration_id' => $registration->id,
                'event_id' => $registration->event_id,
                'ticket_number' => $this->uniqueTicketNumber(),
                'token' => $this->uniqueToken(),
                'ticket_type' => 'general',
                'status' => 'valid',
            ]);
        });
    }

    private function uniqueTicketNumber(): string
    {
        do {
            $number = 'EF-'.Str::upper(Str::random(6));
        } while (Ticket::query()->where('ticket_number', $number)->exists());

        return $number;
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (Ticket::query()->where('token', $token)->exists());

        return $token;
    }
}
