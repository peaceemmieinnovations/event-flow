<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\CheckInService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketManagementController extends Controller
{
    public function __construct(
        private readonly CheckInService $checkIn,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $tickets = Ticket::query()
            ->with(['event:id,title,event_date,capacity', 'registration:id,event_id,first_name,last_name,email,status', 'checkedInBy:id,name'])
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('ticket_number', 'like', '%'.$search.'%')
                        ->orWhereHas('registration', fn ($q) => $q->where('email', 'like', '%'.$search.'%'))
                        ->orWhereHas('registration', fn ($q) => $q->where('last_name', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['event'], fn (Builder $query, int $event) => $query->where('event_id', $event))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'events' => Event::query()->orderBy('event_date')->get(['id', 'title', 'event_date', 'capacity']),
            'filters' => $filters,
            'statuses' => $this->statuses(),
        ]);
    }

    /**
     * Landing screen that lets staff pick the event they are working the door for.
     */
    public function checkinIndex(): View
    {
        $events = Event::query()
            ->withCount([
                'tickets as issued_count',
                'tickets as checked_in_count' => fn ($query) => $query->where('status', Event::TICKET_STATUS_CHECKED_IN),
            ])
            ->orderBy('event_date')
            ->get(['id', 'title', 'event_date', 'location', 'status', 'capacity']);

        return view('admin.tickets.checkin-index', [
            'events' => $events,
        ]);
    }

    public function checkin(Event $event): View
    {
        abort_unless($event->isPublished() || $event->isPast(), 404);

        $event->loadCount([
            'tickets as issued_count',
            'tickets as checked_in_count' => fn ($query) => $query->where('status', Event::TICKET_STATUS_CHECKED_IN),
        ]);

        return view('admin.tickets.checkin', [
            'event' => $event,
            'recentCheckIns' => $event->tickets()
                ->with(['registration:id,first_name,last_name,email'])
                ->where('status', Event::TICKET_STATUS_CHECKED_IN)
                ->latest('checked_in_at')
                ->limit(12)
                ->get(),
        ]);
    }

    /**
     * The scanner posts here. It always answers 200 so the device UI can show the
     * outcome in every case, including a bad or already-used code.
     */
    public function process(Request $request, Event $event): JsonResponse|RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:255']]);

        $result = $this->checkIn->checkIn($event, (string) $request->string('code'));

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()->with('scan', $result);
    }

    /**
     * Non-mutating lookup, so staff can confirm a code before letting anyone in.
     */
    public function verify(Request $request, Event $event): JsonResponse
    {
        $code = (string) $request->string('code');

        return response()->json($this->checkIn->verify($event, $code));
    }

    /**
     * @return array{search: ?string, event: ?int, status: ?string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->filled('search') ? trim((string) $request->string('search')) : null,
            'event' => $request->integer('event') ?: null,
            'status' => in_array((string) $request->string('status'), $this->statuses(), true) ? (string) $request->string('status') : null,
        ];
    }

    /**
     * @return list<string>
     */
    private function statuses(): array
    {
        return Event::TICKET_STATUSES;
    }
}
