<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payment;
use App\Services\CommunicationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventManagementController extends Controller
{
    public function __construct(
        private readonly CommunicationService $communications,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $events = Event::query()
            ->withCount([
                'registrations as registration_count' => fn ($query) => $query->whereIn('status', Event::ACTIVE_REGISTRATION_STATUSES),
                'tickets as ticket_count',
                'tickets as checked_in_count' => fn ($query) => $query->where('status', Event::TICKET_STATUS_CHECKED_IN),
            ])
            ->withSum(['payments as revenue' => fn ($query) => $query->where('status', Payment::STATUS_SUCCESSFUL)], 'amount')
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('title', 'like', '%'.$search.'%')
                        ->orWhere('location', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['type'], fn (Builder $query, string $type) => $query->where('event_type', $type))
            ->when($filters['category'], fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['when'] === 'upcoming', fn (Builder $query) => $query->where('event_date', '>=', now()))
            ->when($filters['when'] === 'past', fn (Builder $query) => $query->where('event_date', '<', now()))
            ->when($filters['sort'] === 'newest', fn (Builder $query) => $query->orderByDesc('event_date'))
            ->when($filters['sort'] === 'title', fn (Builder $query) => $query->orderBy('title'))
            ->orderBy('event_date')
            ->paginate(15)
            ->withQueryString();

        return view('admin.events.index', [
            'events' => $events,
            'filters' => $filters,
            'statuses' => $this->statuses(),
            'types' => $this->types(),
            'summary' => $this->summary(),
        ]);
    }

    public function create(): View
    {
        return view('admin.events.form', [
            'event' => new Event([
                'status' => Event::STATUS_DRAFT,
                'event_type' => Event::TYPE_FREE,
                'currency' => 'NGN',
                'capacity' => 100,
            ]),
            'mode' => 'create',
            'categories' => Event::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = Event::create($this->eventData($request));

        return to_route('admin.events.index')
            ->with('status', "{$event->title} has been created as a {$event->status} event.");
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', [
            'event' => $event,
            'mode' => 'edit',
            'categories' => Event::CATEGORIES,
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $previous = $event->only(['title', 'event_date', 'location', 'start_time', 'end_time', 'capacity', 'price_amount']);
        $event->update($this->eventData($request, $event));

        $this->announceMaterialChange($event, $previous);

        return to_route('admin.events.index')->with('status', "{$event->title} has been updated.");
    }

    public function destroy(Event $event): RedirectResponse
    {
        if ($event->registrations()->exists()) {
            return back()->with('error', 'Events with registrations cannot be deleted. Cancel the event instead.');
        }

        $this->deleteImage($event->image);
        $title = $event->title;
        $event->delete();

        return to_route('admin.events.index')->with('status', "{$title} has been deleted.");
    }

    /**
     * Publishing, cancelling and completing are lifecycle transitions with
     * attendee consequences, so each one is validated and communicated.
     */
    public function setStatus(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in($this->statuses())],
        ]);

        $previous = $event->status;
        $event->update([
            ...$data,
            'published_at' => $data['status'] === Event::STATUS_PUBLISHED
                ? ($event->published_at ?? now())
                : $event->published_at,
        ]);

        if ($previous === $event->status) {
            return back()->with('status', "{$event->title} is already {$event->status}.");
        }

        if ($event->isCancelled()) {
            $event->tickets()->update(['status' => Event::TICKET_STATUS_CANCELLED]);
            $this->communications->eventCancelled($event);
        }

        if ($previous === Event::STATUS_PUBLISHED && $event->isPublished()) {
            $this->announceMaterialChange($event, []);
        }

        return back()->with('status', "{$event->title} is now {$event->status}.");
    }

    /**
     * @param  array<string, mixed>  $previous
     */
    private function announceMaterialChange(Event $event, array $previous): void
    {
        if ($previous === []) {
            return;
        }

        $changes = [];

        foreach (['title', 'event_date', 'location', 'start_time', 'end_time'] as $attribute) {
            if (array_key_exists($attribute, $previous)
                && (string) $previous[$attribute] !== (string) $event->{$attribute}) {
                $changes[] = match ($attribute) {
                    'title' => 'the event name',
                    'event_date' => 'the date',
                    'location' => 'the venue',
                    'start_time' => 'the start time',
                    'end_time' => 'the end time',
                };
            }
        }

        if ($event->wasChanged('capacity') && $event->capacity !== null) {
            $changes[] = 'the capacity';
        }

        if ($changes === []) {
            return;
        }

        $this->communications->eventUpdated(
            $event,
            'We have updated '.implode(' and ', $changes).' for your event. Your ticket stays valid.',
        );
    }

    private function deleteImage(?string $image): void
    {
        if ($image && ! Str::startsWith($image, ['http://', 'https://', '/'])) {
            Storage::disk('public')->delete($image);
        }
    }

    /**
     * @return array{search: ?string, status: ?string, type: ?string, category: ?string, when: ?string, sort: string}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => $request->filled('search') ? trim((string) $request->string('search')) : null,
            'status' => in_array((string) $request->string('status'), $this->statuses(), true) ? (string) $request->string('status') : null,
            'type' => in_array((string) $request->string('type'), $this->types(), true) ? (string) $request->string('type') : null,
            'category' => in_array((string) $request->string('category'), Event::CATEGORIES, true) ? (string) $request->string('category') : null,
            'when' => in_array((string) $request->string('when'), ['upcoming', 'past'], true) ? (string) $request->string('when') : null,
            'sort' => in_array((string) $request->string('sort'), ['soonest', 'newest', 'title'], true) ? (string) $request->string('sort') : 'soonest',
        ];
    }

    /**
     * @return list<string>
     */
    private function statuses(): array
    {
        return Event::STATUSES;
    }

    /**
     * @return list<string>
     */
    private function types(): array
    {
        return Event::TYPES;
    }

    /**
     * @return array<string, int>
     */
    private function summary(): array
    {
        return [
            'total' => Event::query()->count(),
            'published' => Event::published()->count(),
            'drafts' => Event::query()->where('status', Event::STATUS_DRAFT)->count(),
            'upcoming' => Event::published()->where('event_date', '>=', now())->count(),
        ];
    }

    /**
     * Pricing is derived from the event type so a form can never store a price
     * on a free event, or a zero price on a paid one.
     *
     * @return array<string, mixed>
     */
    private function eventData(Request $request, ?Event $event = null): array
    {
        if (blank($request->input('slug'))) {
            $request->merge(['slug' => Str::slug((string) $request->input('title'))]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('events', 'slug')->ignore($event)],
            'description' => ['required', 'string'],
            'event_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(Event::CATEGORIES)],
            'organizer_name' => ['nullable', 'string', 'max:255'],
            'organizer_email' => ['nullable', 'email', 'max:255'],
            'organizer_phone' => ['nullable', 'string', 'max:40'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'event_type' => ['required', Rule::in($this->types())],
            'price_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['required', Rule::in($this->statuses())],
            'registration_deadline' => ['nullable', 'date', 'before_or_equal:event_date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=400,min_height=250'],
        ]);

        if ($data['event_type'] === Event::TYPE_FREE) {
            $data['price_amount'] = 0;
            $data['currency'] = $data['currency'] ?? 'NGN';
        } else {
            // Paystack works in the currency's minor unit, so the form asks for
            // a whole major amount and stores kobo.
            $data['price_amount'] = (int) round(((float) ($data['price_amount'] ?? 0)) * 100);
            $data['currency'] = strtoupper($data['currency'] ?? 'NGN');
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($event?->image);
            $data['image'] = $request->file('image')->store('events', 'public');
        } else {
            unset($data['image']);
        }

        if ($data['status'] === Event::STATUS_PUBLISHED && ! $event?->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
