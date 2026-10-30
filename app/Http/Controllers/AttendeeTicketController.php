<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendeeTicketController extends Controller
{
    /**
     * The signed-in attendee's own ticket wallet.
     */
    public function index(Request $request): View
    {
        $requestedStatus = (string) $request->string('status');
        $statusFilter = in_array($requestedStatus, ['upcoming', 'past', 'used'], true)
            ? $requestedStatus
            : null;

        $tickets = $this->query($statusFilter)->paginate(12);

        return view('attendee.tickets.index', [
            'tickets' => $tickets,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function show(Request $request, string $token): View
    {
        abort_unless(strlen($token) === 64, 404);

        $ticket = Ticket::query()
            ->with(['event', 'registration', 'checkedInBy'])
            ->where('token', $token)
            ->firstOrFail();

        $this->authorizeTicket($request, $ticket);

        return view('attendee.tickets.show', [
            'ticket' => $ticket,
        ]);
    }

    /** @return Builder<Ticket> */
    private function query(?string $status): Builder
    {
        $query = Ticket::query()
            ->whereHas('registration', fn ($query) => $query->where('user_id', auth()->id()))
            ->with(['event', 'registration']);

        match ($status) {
            'upcoming' => $query->whereHas('event', fn ($query) => $query->where('event_date', '>=', now())),
            'past' => $query->whereHas('event', fn ($query) => $query->where('event_date', '<', now())),
            'used' => $query->where('status', Event::TICKET_STATUS_CHECKED_IN),
            default => null,
        };

        return $query->orderByDesc('created_at');
    }

    /**
     * A ticket token alone is a bearer credential, so the signed-in owner must
     * match the registration before the ticket is shown.
     */
    private function authorizeTicket(Request $request, Ticket $ticket): void
    {
        abort_unless(
            (int) $ticket->registration?->user_id === (int) $request->user()->id,
            403,
        );
    }
}
