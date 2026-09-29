@props([
    'event',
    'href' => null,
    'imageUrl' => null,
    'imageAlt' => null,
    'category' => null,
    'price' => null,
    'status' => null,
    'statusTone' => 'neutral',
    'featured' => false,
])

@php
    $title = data_get($event, 'title', 'Untitled event');
    $dateValue = data_get($event, 'event_date');
    $date = $dateValue ? \Illuminate\Support\Carbon::parse($dateValue) : null;
    $image = $imageUrl ?? data_get($event, 'image');
    $imageUrl = filled($image) && ! \Illuminate\Support\Str::startsWith($image, ['http://', 'https://', '/'])
        ? asset('storage/' . $image)
        : $image;
    $resolvedImageAlt = $imageAlt ?? "{$title} event image";
    $displayCategory = $category ?? data_get($event, 'category');
    $displayPrice = $price ?? data_get($event, 'price');
    $displayStatus = $status ?? data_get($event, 'status');
    $eventId = data_get($event, 'id');
    $canResolveEventRoute = blank($href)
        && \Illuminate\Support\Facades\Route::has('events.show')
        && is_numeric($eventId)
        && (int) $eventId > 0;
    $resolvedHref = filled($href)
        ? $href
        : ($canResolveEventRoute ? route('events.show', ['event' => $eventId]) : null);
    $tag = filled($resolvedHref) ? 'a' : 'article';
@endphp

<{{ $tag }}
    {{ $attributes->class(['ef-event-card', 'ef-event-card--featured' => $featured]) }}
    @if ($resolvedHref) href="{{ $resolvedHref }}" aria-label="View details for {{ $title }}" @endif
    data-event-card
    data-reveal
>
    <div class="ef-event-card__media" data-parallax="media">
        <div class="ef-event-card__media-inner" data-event-card-media>
            @if (filled($imageUrl))
                <img class="ef-event-card__image" src="{{ $imageUrl }}" alt="{{ $resolvedImageAlt }}" loading="lazy">
            @else
                <div class="ef-event-card__image-placeholder" aria-hidden="true">EventFlow</div>
            @endif
        </div>

        @if ($featured)
            <span class="ef-event-card__featured">Featured</span>
        @endif
    </div>

    <div class="ef-event-card__content">
        @if ($date || filled($displayCategory))
            <div class="ef-event-card__meta">
                @if ($date)
                    <time class="ef-event-card__date" datetime="{{ $date->toIso8601String() }}">
                        {{ $date->format('D, M j · g:i A') }}
                    </time>
                @endif

                @if (filled($displayCategory))
                    <span class="ef-event-card__category">{{ $displayCategory }}</span>
                @endif
            </div>
        @endif

        <h3 class="ef-event-card__title">{{ $title }}</h3>

        @if (filled(data_get($event, 'description')))
            <p class="ef-event-card__location">{{ \Illuminate\Support\Str::limit(data_get($event, 'description'), 120) }}</p>
        @endif

        @if (filled(data_get($event, 'location')))
            <p class="ef-event-card__location">{{ data_get($event, 'location') }}@if(filled(data_get($event, 'city'))), {{ data_get($event, 'city') }}@endif</p>
        @endif

        @if (filled($displayPrice) || filled($displayStatus))
            <div class="ef-event-card__footer">
                @if (filled($displayPrice))
                    <span class="ef-event-card__price">{{ $displayPrice }}</span>
                @endif

                @if (filled($displayStatus))
                    <span class="ef-badge" data-tone="{{ $statusTone }}">{{ $displayStatus }}</span>
                @endif
            </div>
        @endif
    </div>
</{{ $tag }}>
