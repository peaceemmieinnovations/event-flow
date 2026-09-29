@extends('layouts.app')

@section('title', 'Events')

@section('content')
    @php
        $eventCount = $events->count();
        $hasLeadResult = $eventCount >= 3;
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
                <form class="ef-events-page__search" action="{{ route('events.index') }}" method="get" role="search" aria-describedby="events-search-help">
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
                            placeholder="Search events, experiences, artists..."
                            autocomplete="off"
                            readonly
                            aria-describedby="events-search-help"
                        >
                        <button class="ef-events-page__search-status" type="submit" disabled>Search soon</button>
                    </div>
                    <p class="ef-events-page__toolbar-note" id="events-search-help">Search and filters are being prepared. Browse the current calendar in the meantime.</p>
                </form>

                <fieldset class="ef-events-page__future-controls" disabled aria-describedby="events-search-help">
                    <legend class="ef-sr-only">Future event filters</legend>
                    <button class="ef-events-page__future-control" type="button">
                        <span>Location</span>
                        <span>Coming soon</span>
                    </button>
                    <button class="ef-events-page__future-control" type="button">
                        <span>Date</span>
                        <span>Coming soon</span>
                    </button>
                    <button class="ef-events-page__future-control" type="button">
                        <span>Category</span>
                        <span>Coming soon</span>
                    </button>
                    <button class="ef-events-page__future-control" type="button">
                        <span>Sort</span>
                        <span>Coming soon</span>
                    </button>
                </fieldset>
            </div>

            <nav class="ef-events-page__categories" data-events-categories aria-label="Event categories" aria-describedby="events-search-help">
                <a class="ef-events-page__category" href="{{ route('events.index') }}" data-events-category data-active="true" aria-current="page">All events</a>
                <button class="ef-events-page__category" type="button" disabled data-events-category>Music<span class="ef-sr-only"> category filtering coming soon</span></button>
                <button class="ef-events-page__category" type="button" disabled data-events-category>Arts &amp; Culture<span class="ef-sr-only"> category filtering coming soon</span></button>
                <button class="ef-events-page__category" type="button" disabled data-events-category>Food &amp; Drink<span class="ef-sr-only"> category filtering coming soon</span></button>
                <button class="ef-events-page__category" type="button" disabled data-events-category>Business<span class="ef-sr-only"> category filtering coming soon</span></button>
                <button class="ef-events-page__category" type="button" disabled data-events-category>Wellness<span class="ef-sr-only"> category filtering coming soon</span></button>
                <button class="ef-events-page__category" type="button" disabled data-events-category>Community<span class="ef-sr-only"> category filtering coming soon</span></button>
            </nav>

            @if ($events->isNotEmpty())
                <div class="ef-events-page__results-header" data-events-results-header>
                    <div>
                        <p class="ef-eyebrow">On the calendar</p>
                        <h2 class="ef-heading-md" id="events-results-title">Events to make time for.</h2>
                    </div>
                    <p class="ef-events-page__count">{{ $eventCount }} {{ \Illuminate\Support\Str::plural('event', $eventCount) }}</p>
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
                                data-events-card="{{ $hasLeadResult && $loop->first ? 'lead' : 'supporting' }}"
                            />
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="ef-events-page__empty ef-empty-state" data-events-empty>
                    <p class="ef-eyebrow">No events yet</p>
                    <h2 class="ef-heading-md">New experiences will appear here as they are added.</h2>
                    <p class="ef-body">Come back soon, or return home to explore what EventFlow is building.</p>
                    <x-ui.button variant="secondary" :href="route('home')">Return home</x-ui.button>
                </div>
            @endif
        </div>
    </section>
@endsection
