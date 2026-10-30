<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunicationLog;
use App\Models\Event;
use App\Services\CommunicationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommunicationManagementController extends Controller
{
    public function __construct(
        private readonly CommunicationService $communications,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $logs = CommunicationLog::query()
            ->with(['event:id,title', 'registration:id,first_name,last_name,email,reference'])
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('subject', 'like', '%'.$search.'%')
                        ->orWhere('recipient_email', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['event'], fn (Builder $query, int $event) => $query->where('event_id', $event))
            ->when($filters['type'], fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.communications.index', [
            'logs' => $logs,
            'events' => Event::query()->orderByDesc('event_date')->get(['id', 'title']),
            'filters' => $filters,
            'types' => $this->types(),
            'statuses' => $this->statuses(),
            'summary' => $this->summary(),
        ]);
    }

    public function create(Request $request): View
    {
        $events = Event::query()
            ->orderByDesc('event_date')
            ->get(['id', 'title', 'event_date', 'status']);

        return view('admin.communications.create', [
            'events' => $events,
            'audiences' => $this->communications->audiences(),
            'selectedEvent' => $request->integer('event') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', Rule::exists('events', 'id')],
            'audience' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $event = Event::query()->findOrFail((int) $data['event_id']);

        $recipients = $this->communications->broadcast(
            $event,
            $data['audience'],
            $data['subject'],
            $data['message'],
        );

        return to_route('admin.communications.index')->with(
            'status',
            $recipients === 0
                ? 'That audience had no matching attendees, so nothing was queued.'
                : "Queued {$recipients} message(s) for {$event->title}.",
        );
    }

    /**
     * @return array{search: ?string, event: ?int, type: ?string, status: ?string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->filled('search') ? trim((string) $request->string('search')) : null,
            'event' => $request->integer('event') ?: null,
            'type' => in_array((string) $request->string('type'), $this->types(), true) ? (string) $request->string('type') : null,
            'status' => in_array((string) $request->string('status'), $this->statuses(), true) ? (string) $request->string('status') : null,
        ];
    }

    /**
     * @return list<string>
     */
    private function types(): array
    {
        return [
            CommunicationLog::TYPE_REGISTRATION_CONFIRMATION,
            CommunicationLog::TYPE_TICKET_ISSUED,
            CommunicationLog::TYPE_PAYMENT_RECEIPT,
            CommunicationLog::TYPE_REMINDER_24H,
            CommunicationLog::TYPE_REMINDER_1H,
            CommunicationLog::TYPE_EVENT_UPDATED,
            CommunicationLog::TYPE_EVENT_CANCELLED,
            CommunicationLog::TYPE_REGISTRATION_STATUS,
            CommunicationLog::TYPE_ADMIN_BULK,
        ];
    }

    /**
     * @return list<string>
     */
    private function statuses(): array
    {
        return [
            CommunicationLog::STATUS_QUEUED,
            CommunicationLog::STATUS_SENT,
            CommunicationLog::STATUS_FAILED,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function summary(): array
    {
        return [
            'total' => CommunicationLog::query()->count(),
            'queued' => CommunicationLog::query()->where('status', CommunicationLog::STATUS_QUEUED)->count(),
            'sent' => CommunicationLog::query()->where('status', CommunicationLog::STATUS_SENT)->count(),
            'failed' => CommunicationLog::query()->where('status', CommunicationLog::STATUS_FAILED)->count(),
        ];
    }
}
