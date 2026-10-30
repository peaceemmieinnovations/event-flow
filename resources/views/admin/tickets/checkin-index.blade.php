@extends('layouts.admin')
@section('title', 'Check-In')
@section('eyebrow', 'Admissions')
@section('actions')
    <a class="ef-button ef-button--secondary" href="{{ route('admin.tickets.index') }}">All tickets</a>
@endsection

@section('content')
    @if ($events->isEmpty())
        <section class="ef-panel">
            <x-empty title="No events to work" copy="Create and publish an event, then issue tickets before opening the door.">
                <x-ui.button :href="route('admin.events.create')">Create event</x-ui.button>
            </x-empty>
        </section>
    @else
        <section class="ef-checkin-picker" aria-label="Choose an event to check in">
            @foreach ($events as $event)
                @php
                    $issued = (int) $event->issued_count;
                    $checkedIn = (int) $event->checked_in_count;
                    $remaining = $issued > 0 ? $issued - $checkedIn : 0;
                @endphp
                <a class="ef-checkin-picker__card" href="{{ route('admin.tickets.checkin', $event) }}">
                    <p class="ef-checkin-picker__date">{{ $event->event_date->format('D, M j') }}@if ($event->event_date->isFuture()) @endif</p>
                    <h2 class="ef-heading-md">{{ $event->title }}</h2>
                    <p class="ef-meta">{{ $event->location }}</p>

                    <dl class="ef-checkin-picker__stats">
                        <div>
                            <dt>Issued</dt>
                            <dd>{{ $issued }}</dd>
                        </div>
                        <div>
                            <dt>Checked in</dt>
                            <dd>{{ $checkedIn }}</dd>
                        </div>
                        <div>
                            <dt>Outstanding</dt>
                            <dd>{{ $remaining }}</dd>
                        </div>
                    </dl>

                    <span class="ef-checkin-picker__cta" aria-hidden="true">Open check-in &rarr;</span>
                </a>
            @endforeach
        </section>
    @endif
@endsection
