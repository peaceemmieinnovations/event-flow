@extends('layouts.app')

@section('title', 'Events')

@section('content')
    @php
        $eventCount = $events->count();
        $hasLeadResult = $eventCount >= 3;
        $hasActiveFilters = $filters['query'] || $filters['city'] || $filters['category'] || $filters['type'] || $filters['when'];
    @endphp

    <section class="ef-events-page" data-events-page data-scroll-section aria-labelledby="events-page-title">
        <div class="ef-container">
            <header class="ef-events-page__hero" data-events-hero>
                <p class="ef-eyebrow" data-events-eyebrow>Explore events</p>
                <h1 class="ef-display ef-events-page__title" id="events-page-title" data-events-heading>Find your next experience.</h1>
                <div class="ef-events-page__hero-footer">
                    <p class="ef-lead ef-events-page__copy" data-events-copy>Discover events, gatherings, and experiences worth making time for.</p>
                    <p class="ef-events-page__hero-index" aria-hidden="true">01 / Calendar</p>
                </div>
            </header>

            <div class="ef-events-page__toolbar" data-events-toolbar>
                <form class="ef-events-page__search" action="{{ route('events.index') }}" method="get" role="search">
                    <label class="ef-events-page__search-label" for="events-query">Search events</label>
                    <div class="ef-events-page__search-control">
                        <svg class="ef-events-page__search-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" focusable="false">
                            <circle cx="10.75" cy="10.75" r="5.75" stroke="currentColor" stroke-width="1.5"></circle>
                            <path d="m15 15 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                        </svg>
                        <input
                            class="ef-input ef-events-page__search-input"
                            id="events-query"
                            type="search"
                            name="q"
                            value="{{ $filters['query'] }}"
                            placeholder="Search events, venues, artists..."
                            autocomplete="off"
                            enterkeyhint="search"
                        >
                        <button class="ef-events-page__search-status" type="submit">Search</button>
                    </div>
                </form>

                <div class="ef-events-page__filters">
                    <div class="ef-field">
                        <label class="ef-sr-only" for="filter-city">City</label>
                        <select class="ef-select" id="filter-city" name="city" form="events-filter-form" data-auto-submit>
                            <option value="">All cities</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city }}" @selected($filters['city'] === $city)>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ef-field">
                        <label class="ef-sr-only" for="filter-when">When</label>
                        <select class="ef-select" id="filter-when" name="when" form="events-filter-form" data-auto-submit>
                            <option value="">Any date</option>
                            @foreach ($timeWindows as $key => $label)
                                <option value="{{ $key }}" @selected($filters['when'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ef-field">
                        <label class="ef-sr-only" for="filter-type">Price</label>
                        <select class="ef-select" id="filter-type" name="type" form="events-filter-form" data-auto-submit>
                            <option value="">Free and paid</option>
                            <option value="free" @selected($filters['type'] === 'free')>Free only</option>
                            <option value="paid" @selected($filters['type'] === 'paid')>Paid only</option>
                        </select>
                    </div>

                    <div class="ef-field">
                        <label class="ef-sr-only" for="filter-sort">Sort</label>
                        <select class="ef-select" id="filter-sort" name="sort" form="events-filter-form" data-auto-submit>
                            @foreach ($sorts as $key => $label)
                                <option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <noscript>
                        <button class="ef-button ef-button--secondary" type="submit" form="events-filter-form">Apply filters</button>
                    </noscript>
                </div>
            </div>

            <form id="events-filter-form" method="get" action="{{ route('events.index') }}" class="ef-sr-only">
                @if ($filters['query'])
                    <input type="hidden" name="q" value="{{ $filters['query'] }}">
                @endif
            </form>

            <nav class="ef-events-page__categories" data-events-categories aria-label="Event categories">
                <a
                    class="ef-events-page__category"
                    href="{{ route('events.index', array_filter(['q' => $filters['query'], 'sort' => $filters['sort'] === 'soonest' ? null : $filters['sort']])) }}"
                    data-events-category
                    @if (! $filters['category']) data-active="true" aria-current="page" @endif
                >All events</a>

                @foreach ($categories as $category)
                    <a
                        class="ef-events-page__category"
                        href="{{ route('events.index', array_filter(['category' => $category, 'q' => $filters['query'], 'sort' => $filters['sort'] === 'soonest' ? null : $filters['sort']])) }}"
                        data-events-category
                        @if ($filters['category'] === $category) data-active="true" aria-current="page" @endif
                    >{{ ucwords(str_replace('-', ' ', $category)) }}</a>
                @endforeach
            </nav>

            @if ($hasActiveFilters)
                <div class="ef-events-page__active-filters">
                    <p class="ef-meta">Filtered results</p>
                    <ul>
                        @if ($filters['query'])
                            <li><span class="ef-chip">“{{ $filters['query'] }}”</span></li>
                        @endif
                        @if ($filters['city'])
                            <li><span class="ef-chip">{{ $filters['city'] }}</span></li>
                        @endif
                        @if ($filters['category'])
                            <li><span class="ef-chip">{{ ucwords(str_replace('-', ' ', $filters['category'])) }}</span></li>
                        @endif
                        @if ($filters['type'])
                            <li><span class="ef-chip">{{ ucfirst($filters['type']) }}</span></li>
                        @endif
                        @if ($filters['when'])
                            <li><span class="ef-chip">{{ $timeWindows[$filters['when']] }}</span></li>
                        @endif
                        <li><a class="ef-link" href="{{ route('events.index') }}">Clear all</a></li>
                    </ul>
                </div>
            @endif

            @if ($eventCount > 0)
                <div class="ef-events-page__results-header" data-events-results-header>
                    <div>
                        <p class="ef-eyebrow">On the calendar</p>
                        <h2 class="ef-heading-md" id="events-results-title">Events to make time for.</h2>
                    </div>
                    <p class="ef-events-page__count" aria-live="polite">
                        {{ $events->total() }} {{ \Illuminate\Support\Str::plural('event', $events->total()) }}
                    </p>
                </div>

                <ol
                    class="ef-events-page__grid{{ $eventCount === 1 ? ' ef-events-page__grid--single' : ($eventCount === 2 ? ' ef-events-page__grid--pair' : '') }}"
                    data-events-grid
                    aria-labelledby="events-results-title"
                >
                    @foreach ($events as $event)
                        <li class="ef-events-page__grid-item{{ $hasLeadResult && $loop->first ? ' ef-events-page__grid-item--lead' : '' }}">
                            <x-event-card
                                :event="$event"
                                class="ef-events-page__card"
                                :category="$event->categoryLabel()"
                                :price="$event->priceLabel()"
                                data-events-card="{{ $hasLeadResult && $loop->first ? 'lead' : 'supporting' }}"
                            />
                        </li>
                    @endforeach
                </ol>

                <x-pagination :paginator="$events" />
            @else
                <div class="ef-events-page__empty ef-empty-state" data-events-empty>
                    <p class="ef-eyebrow">Nothing matches</p>
                    <h2 class="ef-heading-md">
                        {{ $hasActiveFilters ? 'No events match those filters yet.' : 'New experiences will appear here as they are added.' }}
                    </h2>
                    <p class="ef-body">
                        {{ $hasActiveFilters
                            ? 'Try widening your search, or clear the filters to see the full calendar.'
                            : 'Come back soon, or return home to explore what EventFlow is building.' }}
                    </p>
                    @if ($hasActiveFilters)
                        <x-ui.button variant="secondary" :href="route('events.index')">Clear filters</x-ui.button>
                    @else
                        <x-ui.button variant="secondary" :href="route('home')">Return home</x-ui.button>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
