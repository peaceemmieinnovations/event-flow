@props([
    'variant' => 'primary',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
    'loading' => false,
    'magnetic' => false,
])

@php
    $isDisabled = $disabled || $loading;
    $classes = ['ef-button', "ef-button--{$variant}"];
@endphp

@if ($href)
    <a
        {{ $attributes->class($classes) }}
        @if (! $isDisabled) href="{{ $href }}" @endif
        @if ($isDisabled) aria-disabled="true" tabindex="-1" @endif
        @if ($loading) aria-busy="true" @endif
        @if ($magnetic) data-magnetic @endif
        data-loading="{{ $loading ? 'true' : 'false' }}"
    >
        <span class="ef-button__spinner" aria-hidden="true"></span>
        <span class="ef-button__label">{{ $slot }}</span>
        @if ($loading)
            <span class="ef-sr-only">Loading</span>
        @endif
    </a>
@else
    <button
        {{ $attributes->class($classes) }}
        type="{{ $type }}"
        @disabled($isDisabled)
        @if ($loading) aria-busy="true" @endif
        @if ($magnetic) data-magnetic @endif
        data-loading="{{ $loading ? 'true' : 'false' }}"
    >
        <span class="ef-button__spinner" aria-hidden="true"></span>
        <span class="ef-button__label">{{ $slot }}</span>
        @if ($loading)
            <span class="ef-sr-only">Loading</span>
        @endif
    </button>
@endif
