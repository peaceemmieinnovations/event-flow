@props([
    'title',
    'copy' => null,
    'icon' => null,
])

<div class="ef-empty-state">
    @if ($icon)
        <span class="ef-empty-state__icon" aria-hidden="true">{!! $icon !!}</span>
    @endif

    <h2 class="ef-heading-md">{{ $title }}</h2>

    @if ($copy)
        <p class="ef-body">{{ $copy }}</p>
    @endif

    @if (trim(strip_tags((string) $slot)) !== '')
        <div class="ef-empty-state__actions">{{ $slot }}</div>
    @endif
</div>