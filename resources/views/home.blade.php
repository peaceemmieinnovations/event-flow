@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <section class="ef-hero" id="discover" data-home-hero data-scroll-section aria-labelledby="hero-title">
        <div class="ef-hero__visual" data-hero-visual>
            <img
                class="ef-hero__media"
                data-hero-media
                data-parallax="hero-media"
                src="https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&amp;fit=crop&amp;w=1920&amp;q=85"
                srcset="https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&amp;fit=crop&amp;w=900&amp;q=75 900w, https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&amp;fit=crop&amp;w=1440&amp;q=80 1440w, https://images.unsplash.com/photo-1506157786151-b8491531f063?auto=format&amp;fit=crop&amp;w=1920&amp;q=85 1920w"
                sizes="100vw"
                width="1920"
                height="1280"
                alt="A crowd enjoying a live music performance beneath blue stage lights"
                loading="eager"
                fetchpriority="high"
                decoding="async"
            >
            <div class="ef-hero__scrim" aria-hidden="true"></div>
        </div>

        <div class="ef-container ef-hero__inner">
            <div class="ef-hero__content">
                <p class="ef-eyebrow ef-hero__eyebrow" data-hero-eyebrow data-reveal>Discover what&rsquo;s next</p>
                <h1 class="ef-display ef-hero__title" id="hero-title" data-reveal>
                    <span class="ef-hero__title-mask"><span data-hero-title-line>Find experiences</span></span>
                    <span class="ef-hero__title-mask"><span data-hero-title-line>worth showing up for.</span></span>
                </h1>
                <p class="ef-hero__copy" data-hero-copy data-reveal>Discover unforgettable events, connect with your city, and make plans that become memories.</p>
                <div class="ef-hero__actions" data-hero-actions data-reveal>
                    <x-ui.button :href="route('events.index')" :magnetic="true">Explore Events</x-ui.button>
                    <x-ui.button variant="secondary" :href="auth()->check() ? route('host.events.create') : route('register')">Create an Event</x-ui.button>
                </div>
            </div>

            <p class="ef-hero__detail" data-hero-detail data-reveal>
                <span>EventFlow</span>
                <span>Live culture, closer to you</span>
            </p>
        </div>

        <div class="ef-hero__scroll" data-hero-scroll-indicator aria-hidden="true">
            <span>Scroll to explore</span>
            <span class="ef-hero__scroll-line"></span>
        </div>
    </section>

    <section class="ef-section ef-featured-events" data-featured-events data-scroll-section aria-labelledby="featured-events-title">
        <div class="ef-container">
            <header class="ef-featured-events__header" data-featured-events-intro data-reveal>
                <div class="ef-featured-events__intro">
                    <p class="ef-eyebrow" data-featured-events-eyebrow>Featured events</p>
                    <h2 class="ef-heading-xl ef-featured-events__heading" id="featured-events-title" data-featured-events-heading>What&rsquo;s happening now.</h2>
                    <p class="ef-lead ef-featured-events__copy" data-featured-events-copy>Curated experiences worth making plans for.</p>
                </div>

                <x-ui.button variant="ghost" :href="route('events.index')" class="ef-featured-events__cta" data-featured-events-cta>
                    Explore all events
                </x-ui.button>
            </header>

            @if ($featuredEvents->isNotEmpty())
                @php
                    $leadEvent = $featuredEvents->first();
                @endphp

                <div
                    class="ef-featured-events__composition{{ $featuredEvents->count() === 1 ? ' ef-featured-events__composition--single' : '' }}"
                    data-featured-events-composition
                >
                    <x-event-card
                        :event="$leadEvent"
                        :featured="true"
                        class="ef-featured-events__card ef-featured-events__lead"
                        data-featured-event-card="lead"
                    />

                    @if ($featuredEvents->count() > 1)
                        <div class="ef-featured-events__supporting" data-featured-events-supporting>
                            @foreach ($featuredEvents->skip(1) as $event)
                                <x-event-card
                                    :event="$event"
                                    class="ef-featured-events__card ef-featured-events__supporting-card"
                                    data-featured-event-card="supporting"
                                />
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="ef-featured-events__empty ef-empty-state" data-featured-events-empty data-reveal>
                    <p class="ef-eyebrow">Nothing featured yet</p>
                    <h3 class="ef-heading-md">New experiences are on the way.</h3>
                    <p class="ef-body">We&rsquo;re curating the next events worth showing up for.</p>
                    <x-ui.button variant="secondary" :href="route('events.index')">Explore all events</x-ui.button>
                </div>
            @endif
        </div>
    </section>

    <section class="ef-section ef-categories" id="categories" data-categories data-scroll-section aria-labelledby="categories-title">
        <div class="ef-container">
            <header class="ef-categories__header" data-categories-intro data-reveal>
                <p class="ef-eyebrow" data-categories-eyebrow>Explore by category</p>
                <h2 class="ef-heading-xl ef-categories__heading" id="categories-title" data-categories-heading>Find your kind of gathering.</h2>
                <p class="ef-lead ef-categories__copy" data-categories-copy>Whatever you&rsquo;re into, there&rsquo;s something worth experiencing.</p>
            </header>

            <ul class="ef-categories__composition" aria-label="Event categories" data-categories-composition>
                @foreach ($categories as $category)
                    <li class="ef-categories__item" data-category-item>
                        <x-category-card :category="$category" :featured="$loop->first" />
                    </li>
                @endforeach
            </ul>

            <x-ui.button variant="secondary" :href="route('events.index')" class="ef-categories__cta" data-categories-cta>
                Browse all events
            </x-ui.button>
        </div>
    </section>

    <section class="ef-storytelling" data-storytelling data-scroll-section aria-labelledby="storytelling-title">
        <div class="ef-storytelling__viewport" data-storytelling-viewport>
            <div class="ef-container ef-storytelling__layout">
                <header class="ef-storytelling__intro" data-storytelling-intro data-reveal>
                    <p class="ef-eyebrow">The EventFlow difference</p>
                    <p class="ef-storytelling__index" aria-hidden="true">01 / 03</p>
                </header>

                <figure class="ef-storytelling__visual" data-storytelling-visual>
                    <div class="ef-storytelling__media" data-storytelling-media>
                        <div class="ef-storytelling__media-inner" data-storytelling-media-inner data-parallax="storytelling-media">
                            <img
                                class="ef-storytelling__image"
                                data-storytelling-image
                                src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&amp;fit=crop&amp;w=2200&amp;q=85"
                                srcset="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&amp;fit=crop&amp;w=900&amp;q=75 900w, https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&amp;fit=crop&amp;w=1440&amp;q=80 1440w, https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&amp;fit=crop&amp;w=2200&amp;q=85 2200w"
                                sizes="100vw"
                                width="2200"
                                height="1467"
                                alt="A crowd gathered at an outdoor music festival beneath warm stage lights"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>
                        <div class="ef-storytelling__scrim" aria-hidden="true"></div>
                    </div>
                    <figcaption>Discover your city, one shared moment at a time.</figcaption>
                </figure>

                <div class="ef-storytelling__statements" data-storytelling-statements>
                    <h2 class="ef-storytelling__statement ef-storytelling__statement--lead" id="storytelling-title" data-storytelling-statement="one" data-reveal>
                        More than an event.<span>An experience.</span>
                    </h2>
                    <p class="ef-storytelling__statement" data-storytelling-statement="two" data-reveal>Leave the routine behind.</p>
                    <p class="ef-storytelling__statement" data-storytelling-statement="three" data-reveal>Make plans worth remembering.</p>
                    <p class="ef-storytelling__supporting" data-storytelling-supporting>People, places, and ideas that make room for the unexpected.</p>
                </div>

                <div class="ef-storytelling__progress" aria-hidden="true">
                    <span>01</span>
                    <span class="ef-storytelling__progress-line"><span data-storytelling-progress-fill></span></span>
                    <span>03</span>
                </div>
            </div>
        </div>
    </section>

    <section class="ef-section ef-discovery" id="discovery" data-discovery data-scroll-section aria-labelledby="discovery-title">
        <div class="ef-container">
            <header class="ef-discovery__header" data-discovery-intro>
                <p class="ef-eyebrow" data-discovery-eyebrow>Discover what&rsquo;s next</p>
                <h2 class="ef-heading-xl ef-discovery__heading" id="discovery-title" data-discovery-heading>Find something worth showing up for.</h2>
                <p class="ef-lead ef-discovery__copy" data-discovery-copy>Explore experiences, gatherings, and moments happening around you.</p>
            </header>

            <div class="ef-discovery__console" data-discovery-console>
                <form class="ef-discovery__search" action="{{ route('events.index') }}" method="get" role="search" aria-describedby="discovery-search-help">
                    <label class="ef-discovery__search-label" for="discovery-query">Search the calendar</label>
                    <div class="ef-discovery__search-control">
                        <svg class="ef-discovery__search-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" focusable="false">
                            <circle cx="10.75" cy="10.75" r="5.75" stroke="currentColor" stroke-width="1.5"></circle>
                            <path d="m15 15 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                        </svg>
                        <input
                            class="ef-input ef-discovery__search-input"
                            id="discovery-query"
                            name="query"
                            type="search"
                            placeholder="Search events, artists, experiences..."
                            autocomplete="off"
                        >
                        <button class="ef-discovery__search-status" type="submit" disabled>
                            Search soon
                        </button>
                    </div>
                    <p class="ef-discovery__availability-note" id="discovery-search-help">Search is being prepared. Browse the current calendar while we finish it.</p>
                </form>

                <div class="ef-discovery__filters" data-discovery-filters>
                    <div class="ef-discovery__filters-intro">
                        <p class="ef-label">Explore the calendar</p>
                        <p class="ef-discovery__availability-note" id="discovery-filters-help">Date and category filters are being prepared.</p>
                    </div>

                    <div class="ef-discovery__filter-rail" aria-label="Discovery filters" aria-describedby="discovery-filters-help">
                        <a class="ef-discovery__filter" href="{{ route('events.index') }}" data-discovery-filter data-active="true">All events</a>

                        @foreach (['Today', 'This week', 'This weekend', 'Music', 'Arts & Culture', 'Food & Drink', 'Business', 'Wellness', 'Community'] as $filter)
                            <button class="ef-discovery__filter" type="button" disabled data-discovery-filter aria-describedby="discovery-filters-help">
                                {{ $filter }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            @if ($discoveryEvents->isNotEmpty())
                @php
                    $discoveryLead = $discoveryEvents->first();
                    $discoveryTitle = data_get($discoveryLead, 'title', 'Untitled event');
                    $discoveryDateValue = data_get($discoveryLead, 'event_date');
                    $discoveryDate = $discoveryDateValue ? \Illuminate\Support\Carbon::parse($discoveryDateValue) : null;
                    $discoveryImage = data_get($discoveryLead, 'image');
                    $discoveryImageUrl = filled($discoveryImage) && ! \Illuminate\Support\Str::startsWith($discoveryImage, ['http://', 'https://', '/'])
                        ? asset('storage/' . $discoveryImage)
                        : $discoveryImage;
                    $discoveryLeadId = data_get($discoveryLead, 'id');
                    $discoveryLeadHref = \Illuminate\Support\Facades\Route::has('events.show')
                        && is_numeric($discoveryLeadId)
                        && (int) $discoveryLeadId > 0
                        ? route('events.show', ['event' => $discoveryLeadId])
                        : null;
                @endphp

                <div class="ef-discovery__results" data-discovery-results>
                    <article class="ef-discovery__feature" data-discovery-feature>
                        @if ($discoveryLeadHref)
                            <a class="ef-discovery__media-link" href="{{ $discoveryLeadHref }}" aria-label="View details for {{ $discoveryTitle }}">
                        @endif
                        <div class="ef-discovery__media" data-discovery-media>
                            <div class="ef-discovery__media-inner" data-discovery-media-inner data-parallax="discovery-media">
                                @if (filled($discoveryImageUrl))
                                    <img
                                        class="ef-discovery__image"
                                        data-discovery-image
                                        src="{{ $discoveryImageUrl }}"
                                        width="1600"
                                        height="2000"
                                        alt="{{ $discoveryTitle }} event image"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                @else
                                    <div class="ef-discovery__media-placeholder" aria-hidden="true">
                                        <span>EventFlow</span>
                                        <span>In person, on purpose.</span>
                                    </div>
                                @endif
                            </div>
                            <div class="ef-discovery__media-scrim" aria-hidden="true"></div>
                        </div>
                        @if ($discoveryLeadHref)
                            </a>
                        @endif

                        <div class="ef-discovery__details" data-discovery-details>
                            <p class="ef-eyebrow">From the calendar</p>

                            @if ($discoveryDate)
                                <time class="ef-discovery__date" datetime="{{ $discoveryDate->toIso8601String() }}">
                                    {{ $discoveryDate->format('D, M j') }} <span aria-hidden="true">&middot;</span> {{ $discoveryDate->format('g:i A') }}
                                </time>
                            @endif

                            <h3 class="ef-discovery__event-title">
                                @if ($discoveryLeadHref)
                                    <a class="ef-discovery__event-link" href="{{ $discoveryLeadHref }}">{{ $discoveryTitle }}</a>
                                @else
                                    {{ $discoveryTitle }}
                                @endif
                            </h3>

                            @if (filled(data_get($discoveryLead, 'location')))
                                <p class="ef-discovery__location">{{ data_get($discoveryLead, 'location') }}</p>
                            @endif

                            @if (filled(data_get($discoveryLead, 'description')))
                                <p class="ef-discovery__description">{{ \Illuminate\Support\Str::limit(data_get($discoveryLead, 'description'), 180) }}</p>
                            @endif

                            <x-ui.button variant="secondary" :href="route('events.index')">Explore the full calendar</x-ui.button>
                        </div>
                    </article>

                    @if ($discoveryEvents->count() > 1)
                        <aside class="ef-discovery__rail" data-discovery-rail aria-labelledby="discovery-rail-title">
                            <div>
                                <p class="ef-eyebrow">Keep exploring</p>
                                <h3 class="ef-heading-md" id="discovery-rail-title">More on the calendar.</h3>
                            </div>

                            <ol class="ef-discovery__rail-list">
                                @foreach ($discoveryEvents->skip(1)->take(2) as $event)
                                    @php
                                        $eventDate = data_get($event, 'event_date')
                                            ? \Illuminate\Support\Carbon::parse(data_get($event, 'event_date'))
                                            : null;
                                        $eventId = data_get($event, 'id');
                                        $eventHref = \Illuminate\Support\Facades\Route::has('events.show')
                                            && is_numeric($eventId)
                                            && (int) $eventId > 0
                                            ? route('events.show', ['event' => $eventId])
                                            : null;
                                    @endphp

                                    <li class="ef-discovery__rail-item" data-discovery-rail-item>
                                        @if ($eventDate)
                                            <time class="ef-discovery__rail-date" datetime="{{ $eventDate->toIso8601String() }}">{{ $eventDate->format('D, M j') }}</time>
                                        @endif
                                        <h4>
                                            @if ($eventHref)
                                                <a class="ef-discovery__rail-link" href="{{ $eventHref }}">{{ data_get($event, 'title', 'Untitled event') }}</a>
                                            @else
                                                {{ data_get($event, 'title', 'Untitled event') }}
                                            @endif
                                        </h4>
                                        @if (filled(data_get($event, 'location')))
                                            <p>{{ data_get($event, 'location') }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>

                            <x-ui.button variant="ghost" :href="route('events.index')">View all events</x-ui.button>
                        </aside>
                    @endif
                </div>
            @else
                <div class="ef-discovery__empty ef-empty-state" data-discovery-empty>
                    <p class="ef-eyebrow">Nothing scheduled yet</p>
                    <h3 class="ef-heading-md">New experiences will appear here as events are added.</h3>
                    <p class="ef-body">The calendar is ready for the next gathering.</p>
                    <x-ui.button variant="secondary" :href="route('events.index')">Browse all events</x-ui.button>
                </div>
            @endif
        </div>
    </section>
@endsection
