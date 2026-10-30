@extends('layouts.admin')
@php
    use App\Support\Money;
@endphp
@section('title', 'Events')
@section('eyebrow', 'Event management')
@section('actions')
    <x-ui.button :href="route('admin.events.create')">Create event</x-ui.button>
@endsection

@section('content')
    <section class="ef-metrics ef-metrics--compact" aria-label="Event totals">
        <x-metric label="All events" :value="number_format($summary['total'])" />
        <x-metric label="Published" :value="number_format($summary['published'])" tone="success" />
        <x-metric label="Drafts" :value="number_format($summary['drafts'])" tone="warning" />
        <x-metric label="Upcoming" :value="number_format($summary['upcoming'])" />
    </section>

    <form class="ef-filters" method="GET" role="search" aria-label="Filter events">
        <div class="ef-filters__search">
            <label class="ef-sr-only" for="event-search">Search events</label>
            <svg class="ef-filters__search-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <circle cx="11" cy="11" r="5.75"></circle>
                <path d="m16 16 4 4"></path>
            </svg>
            <input class="ef-input" id="event-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search title, venue or city">
        </div>

        <div class="ef-filters__group">
            <label class="ef-sr-only" for="filter-status">Status</label>
            <select class="ef-select" id="filter-status" name="status" data-auto-submit>
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>

            <label class="ef-sr-only" for="filter-type">Type</label>
            <select class="ef-select" id="filter-type" name="type" data-auto-submit>
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>

            <label class="ef-sr-only" for="filter-category">Category</label>
            <select class="ef-select" id="filter-category" name="category" data-auto-submit>
                <option value="">All categories</option>
                @foreach (\App\Models\Event::CATEGORIES as $category)
                    <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ ucwords(str_replace('-', ' ', $category)) }}</option>
                @endforeach
            </select>

            <label class="ef-sr-only" for="filter-when">Timing</label>
            <select class="ef-select" id="filter-when" name="when" data-auto-submit>
                <option value="">Any date</option>
                <option value="upcoming" @selected($filters['when'] === 'upcoming')>Upcoming</option>
                <option value="past" @selected($filters['when'] === 'past')>Past</option>
            </select>

            <label class="ef-sr-only" for="filter-sort">Sort</label>
            <select class="ef-select" id="filter-sort" name="sort" data-auto-submit>
                <option value="soonest" @selected($filters['sort'] === 'soonest')>Soonest first</option>
                <option value="newest" @selected($filters['sort'] === 'newest')>Most recently scheduled</option>
                <option value="title" @selected($filters['sort'] === 'title')>A &ndash; Z</option>
            </select>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (array_filter($filters, fn ($value) => $value !== null && $value !== 'soonest'))
                <a class="ef-link" href="{{ route('admin.events.index') }}">Clear</a>
            @endif
        </div>
    </form>

    <section class="ef-panel" aria-label="Events">
        @if ($events->isEmpty())
            <x-empty title="No events yet" copy="Create your first EventFlow experience to begin building the calendar.">
                <x-ui.button :href="route('admin.events.create')">Create event</x-ui.button>
            </x-empty>
        @else
            <div class="ef-table-scroll">
                <table class="ef-table">
                    <caption class="ef-sr-only">Events matching the current filters</caption>
                    <thead>
                        <tr>
                            <th scope="col">Event</th>
                            <th scope="col">When</th>
                            <th scope="col">Type</th>
                            <th scope="col" class="ef-table__numeric">Registered</th>
                            <th scope="col" class="ef-table__numeric">Checked in</th>
                            <th scope="col" class="ef-table__numeric">Revenue</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="ef-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <th scope="row">
                                    <a class="ef-table__primary" href="{{ route('admin.events.edit', $event) }}">{{ $event->title }}</a>
                                    <span class="ef-table__meta">
                                        <span>{{ $event->location }}@if ($event->city), {{ $event->city }}@endif</span>
                                        @if ($event->categoryLabel())
                                            <span>{{ $event->categoryLabel() }}</span>
                                        @endif
                                    </span>
                                </th>
                                <td>
                                    <span class="ef-table__strong">{{ $event->event_date->format('D, M j, Y') }}</span>
                                    <span class="ef-table__meta"><span>{{ $event->startTimeLabel() ?? 'No time set' }}</span></span>
                                </td>
                                <td>
                                    <x-badge :label="$event->isFree() ? 'free' : 'paid'" />
                                    @if ($event->isPaid())
                                        <span class="ef-table__meta"><span>{{ $event->priceLabel() }}</span></span>
                                    @endif
                                </td>
                                <td class="ef-table__numeric">
                                    {{ number_format($event->registration_count) }}
                                    @if ($event->capacity)
                                        <span class="ef-table__meta"><span>of {{ number_format($event->capacity) }}</span></span>
                                    @endif
                                </td>
                                <td class="ef-table__numeric">{{ number_format($event->checked_in_count) }}</td>
                                <td class="ef-table__numeric">{{ Money::compact((int) ($event->revenue ?? 0), $event->currency) }}</td>
                                <td><x-badge :label="$event->status" /></td>
                                <td class="ef-table__actions">
                                    <div class="ef-actions">
                                        <a class="ef-button ef-button--secondary" href="{{ route('admin.events.edit', $event) }}">Edit</a>
                                        <a class="ef-button ef-button--ghost" href="{{ route('admin.events.analytics', $event) }}">Analytics</a>

                                        @if ($event->isPublished())
                                            <form method="POST" action="{{ route('admin.events.status', $event) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ \App\Models\Event::STATUS_DRAFT }}">
                                                <button class="ef-button ef-button--ghost" type="submit">Unpublish</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.events.status', $event) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ \App\Models\Event::STATUS_PUBLISHED }}">
                                                <button class="ef-button ef-button--ghost" type="submit">Publish</button>
                                            </form>
                                        @endif

                                        @if (! $event->isCancelled())
                                            <form method="POST" action="{{ route('admin.events.status', $event) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ \App\Models\Event::STATUS_CANCELLED }}">
                                                <button class="ef-button ef-button--destructive" type="submit" onclick="return confirm('Cancel {{ $event->title }}? Attendees will be notified and tickets invalidated.')">Cancel</button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Delete this event? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ef-button ef-button--ghost" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ef-panel__footer">
                <p class="ef-meta">Showing {{ $events->firstItem() }}&ndash;{{ $events->lastItem() }} of {{ $events->total() }}</p>
                <x-pagination :paginator="$events" />
            </div>
        @endif
    </section>
@endsection
