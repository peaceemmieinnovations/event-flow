@extends('layouts.admin')
@section('title', 'Registrations')
@section('eyebrow', 'Attendees')
@section('actions')
    <a class="ef-button ef-button--secondary" href="{{ route('admin.communications.compose') }}">Message attendees</a>
@endsection

@section('content')
    <form class="ef-filters" method="GET" role="search" aria-label="Filter registrations">
        <div class="ef-filters__search">
            <label class="ef-sr-only" for="registration-search">Search registrations</label>
            <svg class="ef-filters__search-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <circle cx="11" cy="11" r="5.75"></circle>
                <path d="m16 16 4 4"></path>
            </svg>
            <input class="ef-input" id="registration-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search name, email, phone or reference">
        </div>

        <div class="ef-filters__group">
            <label class="ef-sr-only" for="filter-event">Event</label>
            <select class="ef-select" id="filter-event" name="event" data-auto-submit>
                <option value="">All events</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected($filters['event'] === $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>

            <label class="ef-sr-only" for="filter-registration-status">Status</label>
            <select class="ef-select" id="filter-registration-status" name="status" data-auto-submit>
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (array_filter($filters))
                <a class="ef-link" href="{{ route('admin.registrations.index') }}">Clear</a>
            @endif
        </div>
    </form>

    <section class="ef-panel" aria-label="Registrations">
        @if ($registrations->isEmpty())
            <x-empty title="No registrations found" copy="Registrations appear here as soon as attendees sign up, and the filters above let you narrow by event or status." />
        @else
            <div class="ef-table-scroll">
                <table class="ef-table">
                    <caption class="ef-sr-only">Registrations matching the current filters</caption>
                    <thead>
                        <tr>
                            <th scope="col">Attendee</th>
                            <th scope="col">Event</th>
                            <th scope="col">Reference</th>
                            <th scope="col">Registered</th>
                            <th scope="col">Ticket</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="ef-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($registrations as $registration)
                            <tr>
                                <th scope="row">
                                    <a class="ef-table__primary" href="{{ route('admin.registrations.show', $registration) }}">{{ $registration->attendeeName() }}</a>
                                    <span class="ef-table__meta">
                                        <span>{{ $registration->email }}</span>
                                        @if ($registration->phone)
                                            <span>{{ $registration->phone }}</span>
                                        @endif
                                    </span>
                                </th>
                                <td>
                                    <a class="ef-link" href="{{ route('admin.events.analytics', $registration->event) }}">{{ $registration->event->title }}</a>
                                    <span class="ef-table__meta"><span>{{ $registration->event->event_date->format('M j, Y') }}</span></span>
                                </td>
                                <td><code class="ef-code">{{ $registration->reference }}</code></td>
                                <td>{{ $registration->registered_at?->format('M j, Y g:i A') }}</td>
                                <td>
                                    @if ($registration->tickets_count)
                                        <x-badge :label="$registration->tickets_count === 1 ? '1 issued' : $registration->tickets_count.' issued'" />
                                    @else
                                        <span class="ef-meta">None</span>
                                    @endif
                                </td>
                                <td><x-badge :label="$registration->status" /></td>
                                <td class="ef-table__actions">
                                    <a class="ef-button ef-button--ghost" href="{{ route('admin.registrations.show', $registration) }}">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ef-panel__footer">
                <p class="ef-meta">Showing {{ $registrations->firstItem() }}&ndash;{{ $registrations->lastItem() }} of {{ $registrations->total() }}</p>
                <x-pagination :paginator="$registrations" />
            </div>
        @endif
    </section>
@endsection
