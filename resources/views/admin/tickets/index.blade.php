@extends('layouts.admin')
@section('title', 'Tickets')
@section('eyebrow', 'Admissions')
@section('actions')
    <a class="ef-button ef-button--secondary" href="{{ route('admin.tickets.checkin.index') }}">Open check-in</a>
@endsection

@section('content')
    <form class="ef-filters" method="GET" role="search" aria-label="Filter tickets">
        <div class="ef-filters__search">
            <label class="ef-sr-only" for="ticket-search">Search tickets</label>
            <svg class="ef-filters__search-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <circle cx="11" cy="11" r="5.75"></circle>
                <path d="m16 16 4 4"></path>
            </svg>
            <input class="ef-input" id="ticket-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search ticket number, attendee or email">
        </div>

        <div class="ef-filters__group">
            <label class="ef-sr-only" for="filter-ticket-event">Event</label>
            <select class="ef-select" id="filter-ticket-event" name="event" data-auto-submit>
                <option value="">All events</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected($filters['event'] === $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>

            <label class="ef-sr-only" for="filter-ticket-status">Status</label>
            <select class="ef-select" id="filter-ticket-status" name="status" data-auto-submit>
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (array_filter($filters))
                <a class="ef-link" href="{{ route('admin.tickets.index') }}">Clear</a>
            @endif
        </div>
    </form>

    <section class="ef-panel" aria-label="Tickets">
        @if ($tickets->isEmpty())
            <x-empty title="No tickets found" copy="Tickets are issued automatically once a registration is confirmed." />
        @else
            <div class="ef-table-scroll">
                <table class="ef-table">
                    <caption class="ef-sr-only">Tickets matching the current filters</caption>
                    <thead>
                        <tr>
                            <th scope="col">Ticket</th>
                            <th scope="col">Attendee</th>
                            <th scope="col">Event</th>
                            <th scope="col">Checked in</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="ef-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <th scope="row">
                                    <span class="ef-table__primary"><code class="ef-code">{{ $ticket->displayNumber() }}</code></span>
                                    <span class="ef-table__meta"><span>{{ $ticket->ticket_type }}</span></span>
                                </th>
                                <td>
                                    <span class="ef-table__strong">{{ $ticket->registration?->attendeeName() }}</span>
                                    <span class="ef-table__meta"><span>{{ $ticket->registration?->email }}</span></span>
                                </td>
                                <td>
                                    <a class="ef-link" href="{{ route('admin.events.analytics', $ticket->event) }}">{{ $ticket->event?->title }}</a>
                                    <span class="ef-table__meta"><span>{{ $ticket->event?->event_date->format('M j, Y') }}</span></span>
                                </td>
                                <td>
                                    @if ($ticket->checked_in_at)
                                        {{ $ticket->checked_in_at->format('M j, g:i A') }}
                                        @if ($ticket->checkedInBy)
                                            <span class="ef-table__meta"><span>by {{ $ticket->checkedInBy->name }}</span></span>
                                        @endif
                                    @else
                                        <span class="ef-meta">Not yet</span>
                                    @endif
                                </td>
                                <td><x-badge :label="$ticket->status" /></td>
                                <td class="ef-table__actions">
                                    <div class="ef-actions">
                                        <a class="ef-button ef-button--ghost" href="{{ route('tickets.show', $ticket->token) }}">View</a>
                                        @if ($ticket->isValid())
                                            <a class="ef-button ef-button--secondary" href="{{ route('admin.tickets.checkin', $ticket->event) }}">Check in</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ef-panel__footer">
                <p class="ef-meta">Showing {{ $tickets->firstItem() }}&ndash;{{ $tickets->lastItem() }} of {{ $tickets->total() }}</p>
                <x-pagination :paginator="$tickets" />
            </div>
        @endif
    </section>
@endsection
