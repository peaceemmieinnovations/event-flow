<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\View\View;

class TicketController extends Controller
{
    /**
     * The 64-character token in the URL is the ticket's only credential, so the
     * ticket is never looked up by its sequential ID.
     */
    public function show(string $token): View
    {
        return view('tickets.show', [
            'ticket' => $this->findTicket($token),
        ]);
    }

    public function print(string $token): View
    {
        return view('tickets.print', [
            'ticket' => $this->findTicket($token),
        ]);
    }

    private function findTicket(string $token): Ticket
    {
        abort_unless(strlen($token) === 64, 404);

        return Ticket::query()
            ->with(['event', 'registration', 'checkedInBy'])
            ->where('token', $token)
            ->firstOrFail();
    }
}
