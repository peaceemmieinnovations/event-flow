@extends('layouts.app')

@php
    $eventTitle = data_get($event, 'title', 'Untitled event');
    $eventDescription = data_get($event, 'description');
    $eventLocation = data_get($event, 'location');
    $eventDateValue = data_get($event, 'event_date');
    $eventDate = $eventDateValue ? \Illuminate\Support\Carbon::parse($eventDateValue) : null;
    $eventImage = data_get($event, 'image');
    $eventImageUrl = filled($eventImage) && ! \Illuminate\Support\Str::startsWith($eventImage, ['http://', 'https://', '/'])
        ? asset('storage/' . $eventImage)
        : $eventImage;
@endphp

@section('title', $eventTitle)

@section('content')
    <article class="ef-event-detail" data-event-detail data-scroll-section aria-labelledby="event-title">
        <div class="ef-container">
            <a class="ef-event-detail__back ef-link" href="{{ route('events.index') }}" data-event-detail-back>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" focusable="false">
                    <path d="M19 12H5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"></path>
                    <path d="m11 18-6-6 6-6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <span>Back to all events</span>
            </a>

            <header class="ef-event-detail__hero" data-event-detail-hero>
                <div class="ef-event-detail__visual" data-event-detail-visual>
                    <div class="ef-event-detail__media" data-event-detail-media>
                        <div class="ef-event-detail__media-inner" data-event-detail-media-inner data-parallax="event-detail-media">
                            @if (filled($eventImageUrl))
                                <img
                                    class="ef-event-detail__image"
                                    data-event-detail-image
                                    src="{{ $eventImageUrl }}"
                                    width="1800"
                                    height="1200"
                                    alt="{{ $eventTitle }} event image"
                                    loading="eager"
                                    fetchpriority="high"
                                    decoding="async"
                                >
                            @else
                                <div class="ef-event-detail__media-placeholder" aria-hidden="true">
                                    <span>EventFlow</span>
                                    <span>Live culture, closer to you.</span>
                                </div>
                            @endif
                        </div>
                        <div class="ef-event-detail__media-scrim" aria-hidden="true"></div>
                    </div>
                </div>

                <div class="ef-event-detail__hero-content">
                    <p class="ef-eyebrow" data-event-detail-eyebrow>Event details</p>
                    <h1 class="ef-display ef-event-detail__title" id="event-title" data-event-detail-title>{{ $eventTitle }}</h1>

                    @if ($eventDate || filled($eventLocation))
                        <dl class="ef-event-detail__hero-meta" data-event-detail-meta>
                            @if ($eventDate)
                                <div>
                                    <dt>Date and time</dt>
                                    <dd>
                                        <time datetime="{{ $eventDate->toIso8601String() }}">
                                            {{ $eventDate->format('l, F j, Y') }} @if($event->start_time)<span aria-hidden="true">&middot;</span> {{ \Illuminate\Support\Carbon::parse($event->start_time)->format('g:i A') }}@endif
                                        </time>
                                    </dd>
                                </div>
                            @endif

                            @if (filled($eventLocation))
                                <div>
                                    <dt>Location</dt>
                                    <dd>{{ $eventLocation }}</dd>
                                </div>
                            @endif
                            @if (filled($event->address) || filled($event->city))
                                <div><dt>Address</dt><dd>{{ collect([$event->address, $event->city, $event->state, $event->country])->filter()->join(', ') }}</dd></div>
                            @endif
                            @if (filled($event->organizer_name))
                                <div><dt>Organizer</dt><dd>{{ $event->organizer_name }}</dd></div>
                            @endif
                            @if ($event->registration_deadline)
                                <div><dt>Registration deadline</dt><dd>{{ $event->registration_deadline->format('M j, Y g:i A') }}</dd></div>
                            @endif
                            @if ($event->capacity)
                                <div><dt>Capacity</dt><dd>{{ $event->capacity }} places</dd></div>
                            @endif
                        </dl>
                    @endif

                    <div class="ef-event-detail__actions" data-event-detail-action>
                        <div class="ef-event-detail__booking">
                            @if ($event->registrationIsOpen())
                                <x-ui.button :href="route('registrations.create', $event)">Register now</x-ui.button>
                            @else
                                <x-ui.button :disabled="true">Registration unavailable</x-ui.button>
                                <p>Registration is closed, unavailable, or this event is full.</p>
                            @endif
                        </div>
                        <a class="ef-link" href="{{ route('events.index') }}">Browse all events</a>
                    </div>
                </div>
            </header>

            <div class="ef-event-detail__content" data-event-detail-content>
                @if (filled($eventDescription))
                    <section class="ef-event-detail__description" data-event-detail-description aria-labelledby="event-description-title">
                        <p class="ef-eyebrow">About this event</p>
                        <h2 class="ef-heading-lg" id="event-description-title">Make time for the moment.</h2>
                        <p>{{ $eventDescription }}</p>
                    </section>
                @endif

                @if ($eventDate || filled($eventLocation))
                    <aside class="ef-event-detail__information" data-event-detail-information aria-labelledby="event-information-title">
                        <p class="ef-eyebrow">At a glance</p>
                        <h2 class="ef-heading-md" id="event-information-title">Event information</h2>

                        <dl>
                            @if ($eventDate)
                                <div>
                                    <dt>Date and time</dt>
                                    <dd>
                                        <time datetime="{{ $eventDate->toIso8601String() }}">
                                            {{ $eventDate->format('M j, Y') }} <span aria-hidden="true">&middot;</span> {{ $eventDate->format('g:i A') }}
                                        </time>
                                    </dd>
                                </div>
                            @endif

                            @if (filled($eventLocation))
                                <div>
                                    <dt>Location</dt>
                                    <dd>{{ $eventLocation }}</dd>
                                </div>
                            @endif
                        </dl>
                    </aside>
                @endif

                <footer class="ef-event-detail__footer" data-event-detail-footer>
                    <a class="ef-link" href="{{ route('events.index') }}">Back to all events</a>
                </footer>
            </div>
        </div>
    </article>
@endsection
