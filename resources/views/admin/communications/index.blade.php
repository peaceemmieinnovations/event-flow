@extends('layouts.admin')
@section('title', 'Communications')
@section('eyebrow', 'Messaging')
@section('actions')
    <x-ui.button :href="route('admin.communications.compose')">Compose message</x-ui.button>
@endsection

@section('content')
    <section class="ef-metrics ef-metrics--compact" aria-label="Delivery totals">
        <x-metric label="All messages" :value="number_format($summary['total'])" />
        <x-metric label="Delivered" :value="number_format($summary['sent'])" tone="success" />
        <x-metric label="Queued" :value="number_format($summary['queued'])" tone="warning" />
        <x-metric label="Failed" :value="number_format($summary['failed'])" :tone="$summary['failed'] > 0 ? 'danger' : 'neutral'" />
    </section>

    <form class="ef-filters" method="GET" role="search" aria-label="Filter communications">
        <div class="ef-filters__search">
            <label class="ef-sr-only" for="communication-search">Search communications</label>
            <svg class="ef-filters__search-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <circle cx="11" cy="11" r="5.75"></circle>
                <path d="m16 16 4 4"></path>
            </svg>
            <input class="ef-input" id="communication-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search subject, recipient or content">
        </div>

        <div class="ef-filters__group">
            <label class="ef-sr-only" for="filter-communication-event">Event</label>
            <select class="ef-select" id="filter-communication-event" name="event" data-auto-submit>
                <option value="">All events</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected($filters['event'] === $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>

            <label class="ef-sr-only" for="filter-communication-type">Type</label>
            <select class="ef-select" id="filter-communication-type" name="type" data-auto-submit>
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>

            <label class="ef-sr-only" for="filter-communication-status">Status</label>
            <select class="ef-select" id="filter-communication-status" name="status" data-auto-submit>
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (array_filter($filters))
                <a class="ef-link" href="{{ route('admin.communications.index') }}">Clear</a>
            @endif
        </div>
    </form>

    <section class="ef-panel" aria-label="Communications">
        @if ($logs->isEmpty())
            <x-empty title="No messages yet" copy="Confirmations, receipts and reminders are logged here automatically once they are queued.">
                <x-ui.button :href="route('admin.communications.compose')">Compose message</x-ui.button>
            </x-empty>
        @else
            <div class="ef-table-scroll">
                <table class="ef-table">
                    <caption class="ef-sr-only">Communications matching the current filters</caption>
                    <thead>
                        <tr>
                            <th scope="col">Subject</th>
                            <th scope="col">Recipient</th>
                            <th scope="col">Event</th>
                            <th scope="col">Type</th>
                            <th scope="col">Queued</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <th scope="row">
                                    <span class="ef-table__primary">{{ $log->subject ?: 'No subject' }}</span>
                                    @if ($log->message)
                                        <span class="ef-table__meta"><span>{{ \Illuminate\Support\Str::limit($log->message, 90) }}</span></span>
                                    @endif
                                    @if ($log->error_message)
                                        <span class="ef-table__meta"><span class="ef-table__error">{{ $log->error_message }}</span></span>
                                    @endif
                                </th>
                                <td>
                                    <span class="ef-table__strong">{{ $log->recipient_email ?: '—' }}</span>
                                    @if ($log->registration)
                                        <span class="ef-table__meta">
                                            <a class="ef-link" href="{{ route('admin.registrations.show', $log->registration) }}">View registration</a>
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($log->event)
                                        <a class="ef-link" href="{{ route('admin.events.analytics', $log->event) }}">{{ $log->event->title }}</a>
                                    @else
                                        <span class="ef-meta">—</span>
                                    @endif
                                </td>
                                <td>{{ $log->typeLabel() }}</td>
                                <td>{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                <td><x-badge :label="$log->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ef-panel__footer">
                <p class="ef-meta">Showing {{ $logs->firstItem() }}&ndash;{{ $logs->lastItem() }} of {{ $logs->total() }}</p>
                <x-pagination :paginator="$logs" />
            </div>
        @endif
    </section>
@endsection
