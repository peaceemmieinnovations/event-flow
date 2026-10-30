<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Sort options offered by the public calendar.
     *
     * @var array<string, string>
     */
    private const SORTS = [
        'soonest' => 'Soonest first',
        'latest' => 'Latest first',
        'title' => 'A - Z',
        'free' => 'Free first',
    ];

    private const TIME_WINDOWS = [
        'today' => 'Today',
        'weekend' => 'This weekend',
        'month' => 'This month',
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $events = Event::query()
            ->published()
            ->where('event_date', '>=', now()->startOfDay())
            ->when($filters['query'], function (Builder $query, string $search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('location', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%')
                        ->orWhere('organizer_name', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['city'], fn (Builder $query, string $city) => $query->where('city', $city))
            ->when($filters['category'], fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['type'] === 'free', fn (Builder $query) => $query->where('event_type', Event::TYPE_FREE))
            ->when($filters['type'] === 'paid', fn (Builder $query) => $query->where('event_type', Event::TYPE_PAID))
            ->when($filters['when'] === 'today', fn (Builder $query) => $query->whereDate('event_date', today()))
            ->when($filters['when'] === 'month', fn (Builder $query) => $query->whereBetween('event_date', [now()->startOfMonth(), now()->endOfMonth()]))
            ->when($filters['when'] === 'weekend', fn (Builder $query) => $query->whereBetween('event_date', self::weekendWindow()))
            ->when($filters['sort'] === 'latest', fn (Builder $query) => $query->orderByDesc('event_date'))
            ->when($filters['sort'] === 'title', fn (Builder $query) => $query->orderBy('title'))
            ->when($filters['sort'] === 'free', fn (Builder $query) => $query->orderBy('event_type')->orderBy('event_date'))
            ->when($filters['sort'] === 'soonest', fn (Builder $query) => $query->orderBy('event_date'))
            ->paginate(12)
            ->withQueryString();

        return view('events', [
            'events' => $events,
            'filters' => $filters,
            'cities' => $this->cities(),
            'sorts' => self::SORTS,
            'timeWindows' => self::TIME_WINDOWS,
            'categories' => $this->categories(),
        ]);
    }

    /**
     * Saturday 00:00 through Sunday 23:59 of the weekend the visitor asked for.
     *
     * Two traps are avoided: endOfWeek(Carbon::SATURDAY) would run Saturday to
     * Friday, quietly folding five weekdays into a filter labelled "This
     * weekend"; and on Monday the current weekend is already behind us, so the
     * upcoming one is offered instead of an empty calendar.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function weekendWindow(): array
    {
        $saturday = now()->startOfWeek(Carbon::SATURDAY);

        if (now()->greaterThan($saturday->copy()->addDay()->endOfDay())) {
            $saturday = $saturday->copy()->addWeek();
        }

        return [$saturday->copy()->startOfDay(), $saturday->copy()->addDay()->endOfDay()];
    }

    public function show(Event $event): View
    {
        abort_unless($event->isPublished(), 404);

        $event->loadCount([
            'registrations as registration_count' => fn ($query) => $query->whereIn('status', Event::ACTIVE_REGISTRATION_STATUSES),
            'tickets as ticket_count',
        ]);

        $related = Event::query()
            ->published()
            ->where('event_date', '>=', now())
            ->whereKeyNot($event->id)
            ->when($event->category, fn ($query) => $query->where('category', $event->category))
            ->orderBy('event_date')
            ->limit(3)
            ->get();

        if ($related->count() < 3) {
            $related = $related->concat(
                Event::query()
                    ->published()
                    ->where('event_date', '>=', now())
                    ->whereKeyNot($event->id)
                    ->whereNotIn('id', $related->pluck('id'))
                    ->orderBy('event_date')
                    ->limit(3 - $related->count())
                    ->get()
            );
        }

        return view('events.show', [
            'event' => $event,
            'relatedEvents' => $related,
        ]);
    }

    /**
     * @return array{query: ?string, city: ?string, category: ?string, type: ?string, when: ?string, sort: string}
     */
    private function filters(Request $request): array
    {
        return [
            'query' => $request->filled('q') ? trim((string) $request->string('q')) : null,
            'city' => $request->filled('city') ? (string) $request->string('city') : null,
            'category' => $request->filled('category') && in_array((string) $request->string('category'), Event::CATEGORIES, true)
                ? (string) $request->string('category')
                : null,
            'type' => in_array((string) $request->string('type'), [Event::TYPE_FREE, Event::TYPE_PAID], true)
                ? (string) $request->string('type')
                : null,
            'when' => array_key_exists((string) $request->string('when'), self::TIME_WINDOWS)
                ? (string) $request->string('when')
                : null,
            'sort' => array_key_exists((string) $request->string('sort'), self::SORTS)
                ? (string) $request->string('sort')
                : 'soonest',
        ];
    }

    /**
     * @return list<string>
     */
    private function cities(): array
    {
        $cities = Event::query()
            ->published()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->all();

        return array_values(array_map(static fn ($city): string => (string) $city, $cities));
    }

    /**
     * Only categories that actually have published events are offered.
     *
     * @return list<string>
     */
    private function categories(): array
    {
        $categories = Event::query()
            ->published()
            ->whereIn('category', Event::CATEGORIES)
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        return array_values(array_map(static fn ($category): string => (string) $category, $categories));
    }
}
