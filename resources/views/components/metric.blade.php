@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'neutral',
    'trend' => null,
])

<article class="ef-metric" data-tone="{{ $tone }}">
    <p class="ef-metric__label">{{ $label }}</p>
    <p class="ef-metric__value">{{ $value }}</p>

    @if ($trend !== null)
        <p class="ef-metric__trend" data-trend="{{ $trend >= 0 ? 'up' : 'down' }}">
            <span aria-hidden="true">{{ $trend >= 0 ? '▲' : '▼' }}</span>
            {{ number_format(abs($trend), 1) }}%
        </p>
    @endif

    @if ($hint)
        <p class="ef-metric__hint">{{ $hint }}</p>
    @endif
</article>