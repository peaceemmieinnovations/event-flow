@extends('layouts.admin')
@php
    use App\Support\Money;
@endphp
@section('title', $event->title.' analytics')
@section('eyebrow', 'Analytics')
@section('actions')
    <a class="ef-button ef-button--secondary" href="{{ route('admin.events.edit', $event) }}">Edit event</a>
    <a class="ef-button ef-button--primary" href="{{ route('admin.tickets.checkin', $event) }}">Check in</a>
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

    <section class="ef-panel" aria-label="Event summary">
        <div class="ef-panel__header">
            <div>
                <p class="ef-eyebrow">{{ $event->event_date->format('l, F j, Y') }} @if ($event->startTimeLabel()) &middot; {{ $event->startTimeLabel() }} @endif</p>
                <h2 class="ef-heading-md">{{ $event->title }}</h2>
                <p class="ef-meta">{{ $event->addressLine() ?? $event->location }} &middot; <x-badge :label="$event->status" /> &middot; {{ $event->isFree() ? 'Free event' : $event->priceLabel() }}</p>
            </div>
        </div>

        <div class="ef-metrics ef-metrics--compact" aria-label="Event metrics">
            <x-metric label="Registered" :value="number_format($kpis['registered'])" :hint="$kpis['capacity'] ? 'of '.number_format($kpis['capacity']).' places ('.$kpis['fill_rate'].'%)' : 'unlimited capacity'" />
            <x-metric label="Confirmed" :value="number_format($kpis['confirmed'])" tone="success" :hint="$kpis['pending'].' awaiting payment'" />
            <x-metric label="Checked in" :value="number_format($kpis['checked_in'])" :hint="$kpis['attendance_rate'].'% of issued tickets'" />
            <x-metric label="Revenue" :value="Money::compact($kpis['revenue'], $event->currency)" :hint="$kpis['payments'].' payments · avg '.Money::compact($kpis['average_transaction'], $event->currency)" tone="success" />
            <x-metric
                label="Payment issues"
                :value="number_format($kpis['failed_payments'] + $kpis['pending_payments'])"
                :hint="$kpis['failed_payments'].' failed · '.$kpis['pending_payments'].' pending'"
                :tone="$kpis['failed_payments'] + $kpis['pending_payments'] > 0 ? 'warning' : 'neutral'"
            />
            <x-metric label="Cancelled" :value="number_format($kpis['cancelled'])" :hint="$kpis['remaining'] === null ? 'no capacity limit' : $kpis['remaining'].' places left'" />
        </div>
    </section>

    <div class="ef-admin-grid ef-admin-grid--charts">
        <x-chart :series="$registration_trend" label="Registrations" empty-copy="No registrations in this period." />
        <x-chart :series="$revenue_trend" label="Revenue" :prefix="Money::symbol($event->currency)" :empty-copy="'No verified payments in '.$range['label'].'.'" />
    </div>

    <section class="ef-panel" aria-labelledby="breakdown-heading">
        <header class="ef-panel__header">
            <h2 class="ef-heading-md" id="breakdown-heading">Registration breakdown</h2>
            <p class="ef-meta">{{ $range['label'] }}</p>
        </header>

        <ul class="ef-breakdown">
            @foreach ($breakdown as $row)
                <li class="ef-breakdown__item">
                    <x-badge :label="$row['status']" />
                    <span class="ef-breakdown__value">{{ number_format($row['value']) }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
