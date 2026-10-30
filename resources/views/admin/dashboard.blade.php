@extends('layouts.admin')
@php
    use App\Support\Money;
@endphp
@section('title', 'Overview')
@section('eyebrow', 'Workspace')
@section('actions')
    <x-ui.button :href="route('admin.events.create')">Create event</x-ui.button>
@endsection

@section('content')
    <section class="ef-admin-range" aria-label="Reporting period">
        <form method="GET" class="ef-admin-range__form">
            <label class="ef-label" for="range">Reporting period</label>
            <select class="ef-select" id="range" name="range" data-auto-submit>
                @foreach ($ranges as $key => $label)
                    <option value="{{ $key }}" @selected($range['key'] === $key)>{{ $label }}</option>
                @endforeach
            </select>

            @if ($range['key'] === 'custom')
                <label class="ef-sr-only" for="from">From</label>
                <input class="ef-input" id="from" type="date" name="from" value="{{ $range['from']?->toDateString() }}">
                <label class="ef-sr-only" for="to">To</label>
                <input class="ef-input" id="to" type="date" name="to" value="{{ $range['to']?->toDateString() }}">
            @endif

            <x-ui.button type="submit" variant="secondary">Apply</x-ui.button>
        </form>
    </section>

    <section class="ef-metrics" aria-label="Key metrics">
        <x-metric label="Revenue" :value="Money::compact($kpis['revenue'])" :hint="$kpis['successful_payments'].' verified payments'" tone="success" />
        <x-metric label="Registrations" :value="number_format($kpis['registrations'])" :hint="$kpis['confirmed_registrations'].' confirmed · '.$kpis['pending_registrations'].' pending'" />
        <x-metric label="Tickets issued" :value="number_format($kpis['tickets'])" :hint="$kpis['check_ins'].' checked in'" />
        <x-metric label="Live events" :value="number_format($kpis['upcoming_events'])" :hint="$kpis['published_events'].' published of '.$kpis['events'].' total'" />
        <x-metric
            label="Capacity used"
            :value="$capacity['utilisation'].'%'"
            :hint="$capacity['registered'].' of '.$capacity['capacity'].' places across '.$capacity['events'].' events'"
        />
        <x-metric
            label="Needs attention"
            :value="number_format($kpis['pending_payments'] + $kpis['failed_payments'])"
            :hint="$kpis['pending_payments'].' pending · '.$kpis['failed_payments'].' failed'"
            :tone="$kpis['pending_payments'] + $kpis['failed_payments'] > 0 ? 'warning' : 'neutral'"
        />
    </section>

    <div class="ef-admin-grid ef-admin-grid--charts">
        <x-chart :series="$revenue_trend" label="Revenue" :prefix="Money::symbol('NGN')" :empty-copy="'No verified payments in '.$range['label'].'.'" />
        <x-chart :series="$registration_trend" label="Registrations" empty-copy="No registrations in this period." />
    </div>

    <div class="ef-admin-grid ef-admin-grid--split">
        <section class="ef-panel" aria-labelledby="performance-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="performance-heading">Event performance</h2>
                <a class="ef-link" href="{{ route('admin.events.index') }}">All events</a>
            </header>

            @if ($event_performance === [])
                <x-empty title="No events yet" copy="Create and publish an event to see registrations, revenue and check-in rates here.">
                    <x-ui.button :href="route('admin.events.create')">Create event</x-ui.button>
                </x-empty>
            @else
                <div class="ef-table-scroll">
                    <table class="ef-table">
                        <caption class="ef-sr-only">Event performance ordered by revenue</caption>
                        <thead>
                            <tr>
                                <th scope="col">Event</th>
                                <th scope="col" class="ef-table__numeric">Registrations</th>
                                <th scope="col" class="ef-table__numeric">Fill rate</th>
                                <th scope="col" class="ef-table__numeric">Check-ins</th>
                                <th scope="col" class="ef-table__numeric">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($event_performance as $row)
                                <tr>
                                    <th scope="row">
                                        <a class="ef-table__primary" href="{{ route('admin.events.analytics', $row['event']) }}">{{ $row['event']->title }}</a>
                                        <span class="ef-table__meta">
                                            <x-badge :label="$row['event']->status" />
                                            <span>{{ $row['event']->event_date->format('M j, Y') }}</span>
                                        </span>
                                    </th>
                                    <td class="ef-table__numeric">{{ number_format($row['registrations']) }}</td>
                                    <td class="ef-table__numeric">{{ $row['fill_rate'] === null ? '—' : $row['fill_rate'].'%' }}</td>
                                    <td class="ef-table__numeric">{{ number_format($row['check_ins']) }}</td>
                                    <td class="ef-table__numeric">{{ Money::compact($row['revenue']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="ef-panel" aria-labelledby="upcoming-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="upcoming-heading">Recent events</h2>
                <a class="ef-link" href="{{ route('admin.communications.index') }}">Communications</a>
            </header>

            @if ($events->isEmpty())
                <x-empty title="Nothing scheduled" copy="Your calendar is clear. Publishing an event makes it discoverable immediately." />
            @else
                <ul class="ef-list">
                    @foreach ($events as $event)
                        <li class="ef-list__item">
                            <div>
                                <p class="ef-list__title">
                                    <a href="{{ route('admin.events.edit', $event) }}">{{ $event->title }}</a>
                                </p>
                                <p class="ef-list__meta">
                                    {{ $event->event_date->format('D, M j') }}
                                    @if ($event->startTimeLabel()) · {{ $event->startTimeLabel() }} @endif
                                    · {{ $event->location }}
                                </p>
                            </div>
                            <x-badge :label="$event->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
