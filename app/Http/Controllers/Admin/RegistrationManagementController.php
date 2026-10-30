<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Services\CommunicationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationManagementController extends Controller
{
    public function __construct(
        private readonly CommunicationService $communications,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $registrations = Registration::query()
            ->with(['event:id,title,event_date,location'])
            ->withCount('tickets')
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['event'], fn (Builder $query, int $event) => $query->where('event_id', $event))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('registered_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'events' => Event::query()->orderBy('event_date')->get(['id', 'title']),
            'filters' => $filters,
            'statuses' => Registration::STATUSES,
        ]);
    }

    public function show(Registration $registration): View
    {
        return view('admin.registrations.show', [
            'registration' => $registration->load([
                'event',
                'ticket',
                'payments' => fn ($query) => $query->latest('id'),
                'communicationLogs' => fn ($query) => $query->latest('id')->limit(10),
            ]),
        ]);
    }

    /**
     * Cancelling a registration invalidates its ticket, because an attendee who
     * has been let go must not be able to walk in with a live QR code.
     */
    public function updateStatus(Request $request, Registration $registration): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Registration::STATUSES)],
        ]);

        if ($registration->status === $data['status']) {
            return back()->with('status', "This registration is already {$registration->status}.");
        }

        $previous = $registration->status;
        $registration->update($data);

        if ($data['status'] === Registration::STATUS_CANCELLED) {
            $registration->ticket?->update(['status' => Event::TICKET_STATUS_CANCELLED]);
        }

        $this->communications->registrationStatusChanged($registration->fresh(), $previous);

        return back()->with('status', "Registration {$registration->reference} is now {$registration->status}.");
    }

    /**
     * @return array{search: ?string, event: ?int, status: ?string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->filled('search') ? trim((string) $request->string('search')) : null,
            'event' => $request->integer('event') ?: null,
            'status' => in_array((string) $request->string('status'), Registration::STATUSES, true) ? (string) $request->string('status') : null,
        ];
    }
}
