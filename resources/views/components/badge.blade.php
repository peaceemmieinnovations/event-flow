@props([
    'label' => 'Unknown',
    'tone' => null,
])

@php
    $normalised = strtolower((string) $label);

    $tone ??= match (true) {
        in_array($normalised, ['published', 'confirmed', 'successful', 'sent', 'valid', 'attended', 'completed', 'active'], true) => 'success',
        in_array($normalised, ['pending', 'pending_review', 'queued', 'draft', 'used', 'checked_in'], true) => 'warning',
        in_array($normalised, ['cancelled', 'failed', 'abandoned', 'refunded', 'full', 'past', 'expired'], true) => 'danger',
        in_array($normalised, ['paid', 'confirmed_paid'], true) => 'accent',
        default => 'neutral',
    };
@endphp

<span class="ef-badge" data-tone="{{ $tone }}">{{ ucfirst(str_replace('_', ' ', (string) $label)) }}</span>