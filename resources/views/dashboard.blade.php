@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="ef-dashboard" data-scroll-section>
        <div class="ef-container">
            <header class="ef-page-header" data-reveal>
                <div>
                    <p class="ef-eyebrow">Your EventFlow</p>
                    <h1 class="ef-heading-xl">Welcome, {{ $user->name }}.</h1>
                    <p class="ef-lead">
                        @if ($statistics['upcoming'] > 0)
                            You have {{ $statistics['upcoming'] }} upcoming {{ \Illuminate\Support\Str::plural('event', $statistics['upcoming']) }} waiting for you.
                        @else
                            Nothing booked yet. There is plenty to explore.
                        @endif
                    </p>
                </div>

                <div class="ef-dashboard__header-actions">
                    <a class="ef-button ef-button--secondary" href="{{ route('attendee.tickets') }}">My tickets</a>
                    <x-ui.button :href="route('events.index')">Explore events</x-ui.button>
                </div>
            </header>

            @if ($user->isStaff())
                <nav class="ef-dashboard__staff" aria-label="Administration">
                    <p class="ef-eyebrow">You have {{ strtolower($user->roleLabel()) }} access</p>
                    <ul class="ef-dashboard__staff-links">
                        <li><a class="ef-button ef-button--primary" href="{{ route('admin.dashboard') }}">Admin overview</a></li>
                        <li><a class="ef-link" href="{{ route('admin.events.index') }}">Events</a></li>
                        <li><a class="ef-link" href="{{ route('admin.registrations.index') }}">Registrations</a></li>
                        <li><a class="ef-link" href="{{ route('admin.tickets.index') }}">Tickets</a></li>
                        <li><a class="ef-link" href="{{ route('admin.payments.index') }}">Payments</a></li>
                        <li><a class="ef-link" href="{{ route('admin.communications.index') }}">Communications</a></li>
                        <li><a class="ef-link" href="{{ route('admin.analytics.index') }}">Analytics</a></li>
                    </ul>
                </nav>
            @endif

            <dl class="ef-dashboard__account" data-reveal>
                <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>Role</dt><dd>{{ $user->roleLabel() }}</dd></div>
                <div><dt>Registrations</dt><dd>{{ $statistics['total'] }}</dd></div>
                <div><dt>Checked in</dt><dd>{{ $statistics['attended'] }}</dd></div>
            </dl>

            <section class="ef-dashboard__panel" aria-labelledby="upcoming-heading" data-reveal>
                <header class="ef-dashboard__panel-header">
                    <h2 class="ef-heading-lg" id="upcoming-heading">Coming up</h2>
                    @if ($tickets->isNotEmpty())
                        <a class="ef-link" href="{{ route('attendee.tickets') }}">All tickets</a>
                    @endif
                </header>

                @if ($upcoming->isEmpty())
                    <x-empty title="No upcoming events" copy="When you register for something, it shows up here with its ticket and QR code.">
                        <x-ui.button :href="route('events.index')">Explore events</x-ui.button>
                    </x-empty>
                @else
                    <ul class="ef-dashboard__events">
                        @foreach ($upcoming as $registration)
                            <li class="ef-dashboard__event">
                                <div class="ef-dashboard__event-date">
                                    <span class="ef-dashboard__event-day">{{ $registration->event->event_date->format('j') }}</span>
                                    <span class="ef-dashboard__event-month">{{ $registration->event->event_date->format('M') }}</span>
                                </div>

                                <div class="ef-dashboard__event-body">
                                    <p class="ef-eyebrow">
                                        {{ $registration->event->startTimeLabel() ?? 'Time to be confirmed' }}
                                        @if ($registration->event->spotsRemaining() !== null)
                                            &middot; {{ $registration->event->spotsRemaining() }} places left
                                        @endif
                                    </p>
                                    <h3 class="ef-heading-md">
                                        <a href="{{ route('events.show', $registration->event) }}">{{ $registration->event->title }}</a>
                                    </h3>
                                    <p class="ef-body">{{ $registration->event->addressLine() ?? $registration->event->location }}</p>
                                    <p class="ef-meta"><x-badge :label="$registration->status" /> <code class="ef-code">{{ $registration->reference }}</code></p>
                                </div>

                                <div class="ef-dashboard__event-actions">
                                    @if ($registration->ticket)
                                        <a class="ef-button ef-button--primary" href="{{ route('attendee.tickets.show', $registration->ticket->token) }}">View ticket</a>
                                        <a class="ef-link" href="{{ route('tickets.print', $registration->ticket->token) }}">Print</a>
                                    @elseif ($registration->isPending())
                                        <a class="ef-button ef-button--primary" href="{{ route('registrations.confirmation', $registration) }}">Finish payment</a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            @if ($past->isNotEmpty())
                <section class="ef-dashboard__panel" aria-labelledby="past-heading" data-reveal>
                    <header class="ef-dashboard__panel-header">
                        <h2 class="ef-heading-lg" id="past-heading">Your history</h2>
                    </header>

                    <ul class="ef-dashboard__events ef-dashboard__events--past">
                        @foreach ($past->take(6) as $registration)
                            <li class="ef-dashboard__event">
                                <div class="ef-dashboard__event-date">
                                    <span class="ef-dashboard__event-day">{{ $registration->event->event_date->format('j') }}</span>
                                    <span class="ef-dashboard__event-month">{{ $registration->event->event_date->format('M') }}</span>
                                </div>

                                <div class="ef-dashboard__event-body">
                                    <h3 class="ef-heading-md">{{ $registration->event->title }}</h3>
                                    <p class="ef-meta"><x-badge :label="$registration->status" /> {{ $registration->event->location }}</p>
                                </div>

                                <div class="ef-dashboard__event-actions">
                                    <a class="ef-link" href="{{ route('events.show', $registration->event) }}">View event</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($recommended->isNotEmpty())
                <section class="ef-dashboard__panel" aria-labelledby="recommended-heading" data-reveal>
                    <header class="ef-dashboard__panel-header">
                        <h2 class="ef-heading-lg" id="recommended-heading">You might like</h2>
                        <a class="ef-link" href="{{ route('events.index') }}">See all</a>
                    </header>

                    <div class="ef-events-page__grid">
                        @foreach ($recommended as $event)
                            <x-event-card :event="$event" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>
@endsection
