@props([
    'category',
    'href' => null,
    'featured' => false,
])

@php
    $name = data_get($category, 'name', 'Untitled category');
    $slug = data_get($category, 'slug', \Illuminate\Support\Str::slug($name));
    $description = data_get($category, 'description');
    $image = data_get($category, 'image');
    $imageAlt = data_get($category, 'image_alt', "{$name} event category");
    $headingId = "category-{$slug}-title";
    $tag = filled($href) ? 'a' : 'article';
@endphp

<{{ $tag }}
    {{ $attributes->class(['ef-category-card', 'ef-category-card--featured' => $featured]) }}
    @if ($href) href="{{ $href }}" aria-label="Explore {{ $name }} events" @endif
    data-category-card
    data-category-slug="{{ $slug }}"
    data-reveal
    aria-labelledby="{{ $headingId }}"
>
    <div class="ef-category-card__media" data-category-media>
        <div class="ef-category-card__media-inner" data-category-media-inner data-parallax="category-media">
            @if (filled($image))
                <img
                    class="ef-category-card__image"
                    src="{{ $image }}"
                    alt="{{ $imageAlt }}"
                    width="1600"
                    height="1200"
                    loading="lazy"
                    decoding="async"
                >
            @else
                <div class="ef-category-card__placeholder" aria-hidden="true">{{ $name }}</div>
            @endif
        </div>
        <div class="ef-category-card__scrim" aria-hidden="true"></div>
    </div>

    <div class="ef-category-card__content">
        <p class="ef-category-card__eyebrow">Category</p>
        <h3 class="ef-category-card__title" id="{{ $headingId }}">{{ $name }}</h3>
        @if (filled($description))
            <p class="ef-category-card__description">{{ $description }}</p>
        @endif
    </div>
</{{ $tag }}>
