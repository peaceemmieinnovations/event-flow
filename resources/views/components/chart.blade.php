@props([
    'series' => [],
    'label' => 'Chart',
    'prefix' => '',
    'emptyCopy' => 'No data for this period yet.',
])

@php
    $values = array_map(static fn (array $point): float => (float) $point['value'], $series);
    $max = max($values ?: [0]);
    $max = $max > 0 ? $max : 1;
    $total = array_sum($values);
    $points = count($series);
@endphp

<figure class="ef-chart">
    <figcaption class="ef-chart__caption">
        <span>{{ $label }}</span>
        <span class="ef-chart__total">{{ $prefix }}{{ $total >= 1000 ? number_format($total / 1000, 1).'k' : number_format($total, $total == (int) $total ? 0 : 1) }}</span>
    </figcaption>

    @if ($points === 0 || $max === 0)
        <p class="ef-chart__empty">{{ $emptyCopy }}</p>
    @else
        <div class="ef-chart__plot" role="img" aria-label="{{ $label }}: {{ $points }} data points, total {{ $prefix }}{{ number_format($total) }}">
            @foreach ($series as $index => $point)
                @php $height = max(4, round(((float) $point['value'] / $max) * 100)); @endphp
                <span class="ef-chart__bar" style="--ef-chart-height: {{ $height }}%" title="{{ $point['label'] }}: {{ $prefix }}{{ number_format((float) $point['value']) }}">
                    <span class="ef-sr-only">{{ $point['label'] }}: {{ $prefix }}{{ number_format((float) $point['value']) }}</span>
                </span>
            @endforeach
        </div>

        <ol class="ef-chart__axis" aria-hidden="true">
            @foreach ($series as $point)
                <li>{{ $point['label'] }}</li>
            @endforeach
        </ol>
    @endif
</figure>