<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Supported date filters for the dashboard and analytics screens.
     *
     * @var array<string, string>
     */
    public const RANGES = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last_7_days' => 'Last 7 days',
        'last_30_days' => 'Last 30 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
        'all_time' => 'All time',
        'custom' => 'Custom range',
    ];

    /**
     * Resolve a range key (plus optional custom bounds) into concrete dates.
     *
     * @param  array<string, mixed>  $input
     * @return array{key: string, label: string, from: ?CarbonImmutable, to: ?CarbonImmutable}
     */
    public function resolveRange(array $input): array
    {
        $key = (string) ($input['range'] ?? 'last_30_days');

        if (! array_key_exists($key, self::RANGES)) {
            $key = 'last_30_days';
        }

        if ($key === 'custom') {
            $from = filled($input['from'] ?? null) ? CarbonImmutable::parse($input['from'])->startOfDay() : null;
            $to = filled($input['to'] ?? null) ? CarbonImmutable::parse($input['to'])->endOfDay() : null;

            return ['key' => 'custom', 'label' => 'Custom range', 'from' => $from, 'to' => $to];
        }

        [$from, $to] = match ($key) {
            'today' => [CarbonImmutable::today(), CarbonImmutable::today()->endOfDay()],
            'yesterday' => [CarbonImmutable::yesterday()->startOfDay(), CarbonImmutable::yesterday()->endOfDay()],
            'last_7_days' => [CarbonImmutable::today()->subDays(6)->startOfDay(), CarbonImmutable::today()->endOfDay()],
            'last_30_days' => [CarbonImmutable::today()->subDays(29)->startOfDay(), CarbonImmutable::today()->endOfDay()],
            'this_month' => [CarbonImmutable::today()->startOfMonth(), CarbonImmutable::today()->endOfMonth()],
            'last_month' => [CarbonImmutable::today()->subMonthNoOverflow()->startOfMonth(), CarbonImmutable::today()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [CarbonImmutable::today()->startOfYear(), CarbonImmutable::today()->endOfYear()],
            default => [null, null],
        };

        return ['key' => $key, 'label' => self::RANGES[$key], 'from' => $from, 'to' => $to];
    }

    /**
     * Platform-wide KPIs. Only verified successful payments count as revenue.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function overview(array $input): array
    {
        $range = $this->resolveRange($input);

        $registrations = Registration::query();
        $payments = Payment::query();
        $checkIns = Ticket::query()->where('status', Event::TICKET_STATUS_CHECKED_IN);

        $this->applyRange($registrations, $range, 'registered_at');
        $this->applyRange($payments, $range, 'created_at');
        $this->applyRange($checkIns, $range, 'checked_in_at');

        $revenue = (clone $payments)->where('status', Payment::STATUS_SUCCESSFUL);

        $eventQuery = Event::query();

        return [
            'range' => $range,
            'kpis' => [
                'events' => Event::query()->count(),
                'published_events' => Event::published()->count(),
                'upcoming_events' => Event::query()->where('event_date', '>=', now())->where('status', Event::STATUS_PUBLISHED)->count(),
                'registrations' => (clone $registrations)->count(),
                'confirmed_registrations' => (clone $registrations)->where('status', Registration::STATUS_CONFIRMED)->count(),
                'pending_registrations' => (clone $registrations)->where('status', Registration::STATUS_PENDING)->count(),
                'cancelled_registrations' => (clone $registrations)->where('status', Registration::STATUS_CANCELLED)->count(),
                'tickets' => Ticket::query()->count(),
                'check_ins' => (clone $checkIns)->count(),
                'revenue' => (int) (clone $revenue)->sum('amount'),
                'successful_payments' => (clone $revenue)->count(),
                'pending_payments' => Payment::query()->where('status', Payment::STATUS_PENDING)->count(),
                'failed_payments' => Payment::query()->whereIn('status', [Payment::STATUS_FAILED, Payment::STATUS_ABANDONED])->count(),
                'average_transaction' => (int) round((float) ((clone $revenue)->avg('amount')) ?: 0),
            ],
            'revenue_trend' => $this->revenueTrend($range),
            'registration_trend' => $this->registrationTrend($range),
            'event_performance' => $this->eventPerformance(),
            'capacity' => $this->capacitySummary($eventQuery),
        ];
    }

    /**
     * Analytics for a single event.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function forEvent(Event $event, array $input): array
    {
        $range = $this->resolveRange($input);

        $registrations = Registration::query()->where('event_id', $event->id);
        $payments = Payment::query()->where('event_id', $event->id);
        $tickets = Ticket::query()->where('event_id', $event->id);

        $this->applyRange($registrations, $range, 'registered_at');
        $this->applyRange($payments, $range, 'created_at');
        $this->applyRange($tickets, $range, 'checked_in_at');

        $successful = (clone $payments)->where('status', Payment::STATUS_SUCCESSFUL);

        return [
            'range' => $range,
            'kpis' => [
                'registered' => $event->registeredCount(),
                'confirmed' => (clone $registrations)->where('status', Registration::STATUS_CONFIRMED)->count(),
                'pending' => (clone $registrations)->where('status', Registration::STATUS_PENDING)->count(),
                'cancelled' => (clone $registrations)->where('status', Registration::STATUS_CANCELLED)->count(),
                'tickets_issued' => (clone $tickets)->count(),
                'checked_in' => (clone $tickets)->where('status', Event::TICKET_STATUS_CHECKED_IN)->count(),
                'revenue' => (int) (clone $successful)->sum('amount'),
                'payments' => (clone $successful)->count(),
                'failed_payments' => Payment::query()->where('event_id', $event->id)->whereIn('status', [Payment::STATUS_FAILED, Payment::STATUS_ABANDONED])->count(),
                'pending_payments' => Payment::query()->where('event_id', $event->id)->where('status', Payment::STATUS_PENDING)->count(),
                'average_transaction' => (int) round((float) ((clone $successful)->avg('amount')) ?: 0),
                'capacity' => $event->capacity,
                'fill_rate' => $event->fillRate(),
                'remaining' => $event->spotsRemaining(),
                'attendance_rate' => $event->issuedTicketCount() > 0
                    ? round($event->checkedInCount() / $event->issuedTicketCount() * 100, 1)
                    : 0.0,
            ],
            'registration_trend' => $this->registrationTrend($range, $event),
            'revenue_trend' => $this->revenueTrend($range, $event),
        ];
    }

    /**
     * Daily revenue for the resolved range.
     *
     * @param  array{key: string, label: string, from: ?CarbonImmutable, to: ?CarbonImmutable}  $range
     * @return list<array{label: string, value: float}>
     */
    public function revenueTrend(array $range, ?Event $event = null): array
    {
        $query = Payment::query()
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->selectRaw('DATE(paid_at) as bucket')
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as transactions')
            ->groupBy('bucket')
            ->orderBy('bucket');

        if ($event) {
            $query->where('event_id', $event->id);
        }

        $this->applyRange($query, $range, 'paid_at');

        $rows = $query->get()->keyBy('bucket');

        return $this->fillSeries($range, $rows, 'total');
    }

    /**
     * Daily registration counts for the resolved range.
     *
     * @param  array{key: string, label: string, from: ?CarbonImmutable, to: ?CarbonImmutable}  $range
     * @return list<array{label: string, value: float}>
     */
    public function registrationTrend(array $range, ?Event $event = null): array
    {
        $query = Registration::query()
            ->selectRaw('DATE(registered_at) as bucket')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('bucket')
            ->orderBy('bucket');

        if ($event) {
            $query->where('event_id', $event->id);
        }

        $this->applyRange($query, $range, 'registered_at');

        $rows = $query->get()->keyBy('bucket');

        return $this->fillSeries($range, $rows, 'total');
    }

    /**
     * Registration status split for the current range.
     *
     * @param  array{key: string, label: string, from: ?CarbonImmutable, to: ?CarbonImmutable}  $range
     * @return list<array{status: string, label: string, value: int}>
     */
    public function registrationBreakdown(array $range, ?Event $event = null): array
    {
        $query = Registration::query()
            ->select(['status', DB::raw('COUNT(*) as total')])
            ->groupBy('status');

        if ($event) {
            $query->where('event_id', $event->id);
        }

        $this->applyRange($query, $range, 'registered_at');

        $counts = $query->pluck('total', 'status');

        return array_values(collect(Registration::STATUSES)
            ->map(fn (string $status): array => [
                'status' => $status,
                'label' => ucfirst($status),
                'value' => (int) ($counts[$status] ?? 0),
            ])
            ->all());
    }

    /**
     * Top performing events using a single aggregated query (no N+1).
     *
     * @return list<array<string, mixed>>
     */
    public function eventPerformance(int $limit = 8): array
    {
        return array_values(Event::query()
            ->withCount([
                'registrations as registration_count' => fn ($query) => $query->whereIn('status', Event::ACTIVE_REGISTRATION_STATUSES),
                'tickets as ticket_count',
                'tickets as checked_in_count' => fn ($query) => $query->where('status', Event::TICKET_STATUS_CHECKED_IN),
            ])
            ->withSum(['payments as revenue' => fn ($query) => $query->where('status', Payment::STATUS_SUCCESSFUL)], 'amount')
            ->orderByDesc('revenue')
            ->orderByDesc('registration_count')
            ->limit($limit)
            ->get()
            ->map(fn (Event $event): array => [
                'event' => $event,
                'registrations' => (int) $event->getAttribute('registration_count'),
                'tickets' => (int) $event->getAttribute('ticket_count'),
                'check_ins' => (int) $event->getAttribute('checked_in_count'),
                'revenue' => (int) ($event->getAttribute('revenue') ?? 0),
                'fill_rate' => $event->fillRate(),
            ])
            ->all());
    }

    /**
     * Overall capacity utilisation across published, upcoming events.
     *
     * @param  Builder<Event>|null  $query
     * @return array<string, mixed>
     */
    public function capacitySummary(?Builder $query = null): array
    {
        $events = ($query ?? Event::query())
            ->published()
            ->where('event_date', '>=', now())
            ->withCount(['registrations as registration_count' => fn ($q) => $q->whereIn('status', Event::ACTIVE_REGISTRATION_STATUSES)])
            ->get(['id', 'title', 'capacity', 'event_date']);

        $capacity = (int) $events->sum('capacity');
        $registered = (int) $events->sum('registration_count');

        return [
            'events' => $events->count(),
            'capacity' => $capacity,
            'registered' => $registered,
            'remaining' => max(0, $capacity - $registered),
            'utilisation' => $capacity > 0 ? round($registered / $capacity * 100, 1) : 0.0,
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{key: string, label: string, from: ?CarbonImmutable, to: ?CarbonImmutable}  $range
     */
    private function applyRange(Builder $query, array $range, string $column): void
    {
        if ($range['from']) {
            $query->where($column, '>=', $range['from']);
        }

        if ($range['to']) {
            $query->where($column, '<=', $range['to']);
        }
    }

    /**
     * Build a dense daily series so charts never show misleading gaps.
     *
     * @template TRow of object
     *
     * @param  array{key: string, label: string, from: ?CarbonImmutable, to: ?CarbonImmutable}  $range
     * @param  Collection<int|string, TRow>  $rows
     * @return list<array{label: string, value: float}>
     */
    private function fillSeries(array $range, Collection $rows, string $column): array
    {
        if (! $range['from'] || ! $range['to']) {
            return array_values($rows
                ->map(fn (object $row, int|string $key): array => [
                    'label' => (string) $key,
                    'value' => (float) $row->{$column},
                ])
                ->all());
        }

        $series = [];
        $cursor = $range['from']->copy()->startOfDay();
        $end = $range['to']->copy()->startOfDay();

        // Guard against pathological ranges creating unbounded work.
        $maxDays = 400;
        $days = 0;

        while ($cursor->lessThanOrEqualTo($end) && $days < $maxDays) {
            $key = $cursor->toDateString();
            $row = $rows->get($key);

            $series[] = [
                'label' => $cursor->format('M j'),
                'value' => $row ? (float) $row->{$column} : 0.0,
            ];

            $cursor = $cursor->addDay();
            $days++;
        }

        return $series;
    }
}
