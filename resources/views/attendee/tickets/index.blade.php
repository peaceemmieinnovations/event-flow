@extends('layouts.app')
@section('title', 'My tickets')
@section('content')
    <section class="ef-wallet" data-scroll-section>
        <div class="ef-container">
            <header class="ef-page-header">
                <div>
                    <p class="ef-eyebrow">Attendee area</p>
                    <h1 class="ef-heading-xl">My tickets</h1>
                    <p class="ef-lead">Every ticket you have claimed, ready to scan or print.</p>
                </div>
                <a class="ef-button ef-button--secondary" href="{{ route('dashboard') }}">Dashboard</a>
            </header>

            <nav class="ef-tabs" aria-label="Ticket filters">
                <a class="ef-tabs__tab @if ($statusFilter === null) is-active @endif" href="{{ route('attendee.tickets') }}" @if ($statusFilter === null) aria-current="page" @endif>All</a>
                <a class="ef-tabs__tab @if ($statusFilter === 'upcoming') is-active @endif" href="{{ route('attendee.tickets', ['status' => 'upcoming']) }}" @if ($statusFilter === 'upcoming') aria-current="page" @endif>Upcoming</a>
                <a class="ef-tabs__tab @if ($statusFilter === 'used') is-active @endif" href="{{ route('attendee.tickets', ['status' => 'used']) }}" @if ($statusFilter === 'used') aria-current="page" @endif>Checked in</a>
                <a class="ef-tabs__tab @if ($statusFilter === 'past') is-active @endif" href="{{ route('attendee.tickets', ['status' => 'past']) }}" @if ($statusFilter === 'past') aria-current="page" @endif>Past</a>
            </nav>

            @if ($tickets->isEmpty())
                <div class="ef-wallet__empty">
                    <x-empty title="No tickets yet" copy="Once you register for an event your ticket appears here with a scannable QR code.">
                        <x-ui.button :href="route('events.index')">Explore events</x-ui.button>
                    </x-empty>
                </div>
            @else
                <ul class="ef-wallet__list">
                    @foreach ($tickets as $ticket)
                        <li class="ef-wallet__item">
                            <div class="ef-wallet__meta">
                                <p class="ef-eyebrow">
                                    {{ $ticket->event?->event_date->format('D, M j, Y') }}
                                    @if ($ticket->event?->startTimeLabel()) &middot; {{ $ticket->event->startTimeLabel() }} @endif
                                </p>
                                <h2 class="ef-heading-md">
                                    <a href="{{ route('attendee.tickets.show', $ticket->token) }}">{{ $ticket->event?->title }}</a>
                                </h2>
                                <p class="ef-body">{{ $ticket->event?->addressLine() ?? $ticket->event?->location }}</p>
                                <p class="ef-meta"><code class="ef-code">{{ $ticket->displayNumber() }}</code></p>
                            </div>

                            <div class="ef-wallet__actions">
                                <x-badge :label="$ticket->status" />
                                <a class="ef-button ef-button--secondary" href="{{ route('attendee.tickets.show', $ticket->token) }}">Open</a>
                                <a class="ef-button ef-button--ghost" href="{{ route('tickets.print', $ticket->token) }}">Print</a>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <x-pagination :paginator="$tickets" />
            @endif
        </div>
    </section>
@endsection
