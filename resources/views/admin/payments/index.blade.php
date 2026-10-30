@extends('layouts.admin')
@php
    use App\Support\Money;
@endphp
@section('title', 'Payments')
@section('eyebrow', 'Revenue')
@section('actions')
    <a class="ef-button ef-button--secondary" href="{{ route('admin.events.index') }}">Events</a>
@endsection

@section('content')
    <section class="ef-metrics ef-metrics--compact" aria-label="Payment totals">
        <x-metric
            label="Collected"
            :value="Money::compact($summary['successful_total'])"
            :hint="$summary['successful_count'].' verified payments'"
            tone="success"
        />
        <x-metric
            label="Awaiting payment"
            :value="Money::compact($summary['pending_total'])"
            :hint="$summary['pending_count'].' pending transactions'"
            :tone="$summary['pending_count'] > 0 ? 'warning' : 'neutral'"
        />
        <x-metric
            label="Failed or abandoned"
            :value="Money::compact($summary['problem_total'])"
            :hint="$summary['problem_count'].' need review'"
            :tone="$summary['problem_count'] > 0 ? 'danger' : 'neutral'"
        />
        <x-metric
            label="Webhook"
            :value="$summary['webhook_configured'] ? 'Active' : 'Not configured'"
            :hint="$summary['webhook_configured'] ? 'Signatures are verified' : 'Set PAYSTACK_WEBHOOK_SECRET to verify deliveries'"
            :tone="$summary['webhook_configured'] ? 'success' : 'warning'"
        />
    </section>

    <form class="ef-filters" method="GET" role="search" aria-label="Filter payments">
        <div class="ef-filters__search">
            <label class="ef-sr-only" for="payment-search">Search payments</label>
            <svg class="ef-filters__search-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <circle cx="11" cy="11" r="5.75"></circle>
                <path d="m16 16 4 4"></path>
            </svg>
            <input class="ef-input" id="payment-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search reference, transaction id or email">
        </div>

        <div class="ef-filters__group">
            <label class="ef-sr-only" for="filter-payment-event">Event</label>
            <select class="ef-select" id="filter-payment-event" name="event" data-auto-submit>
                <option value="">All events</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected($filters['event'] === $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>

            <label class="ef-sr-only" for="filter-payment-status">Status</label>
            <select class="ef-select" id="filter-payment-status" name="status" data-auto-submit>
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>

            <label class="ef-sr-only" for="filter-payment-gateway">Gateway</label>
            <select class="ef-select" id="filter-payment-gateway" name="gateway" data-auto-submit>
                <option value="">All gateways</option>
                <option value="paystack" @selected($filters['gateway'] === 'paystack')>Paystack</option>
            </select>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (array_filter($filters))
                <a class="ef-link" href="{{ route('admin.payments.index') }}">Clear</a>
            @endif
        </div>
    </form>

    <section class="ef-panel" aria-label="Payments">
        @if ($payments->isEmpty())
            <x-empty title="No payments found" copy="Payments are created when an attendee reaches checkout for a paid event." />
        @else
            <div class="ef-table-scroll">
                <table class="ef-table">
                    <caption class="ef-sr-only">Payments matching the current filters</caption>
                    <thead>
                        <tr>
                            <th scope="col">Reference</th>
                            <th scope="col">Attendee</th>
                            <th scope="col">Event</th>
                            <th scope="col" class="ef-table__numeric">Amount</th>
                            <th scope="col">Paid at</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="ef-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <th scope="row">
                                    <a class="ef-table__primary" href="{{ route('admin.payments.show', $payment) }}"><code class="ef-code">{{ $payment->reference }}</code></a>
                                    <span class="ef-table__meta"><span>{{ ucfirst($payment->gateway) }}</span></span>
                                </th>
                                <td>
                                    <span class="ef-table__strong">{{ $payment->registration?->attendeeName() }}</span>
                                    <span class="ef-table__meta"><span>{{ $payment->registration?->email }}</span></span>
                                </td>
                                <td>
                                    <a class="ef-link" href="{{ route('admin.events.analytics', $payment->event) }}">{{ $payment->event?->title }}</a>
                                </td>
                                <td class="ef-table__numeric">{{ $payment->formattedAmountWithCode() }}</td>
                                <td>{{ $payment->paid_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                <td><x-badge :label="$payment->status" /></td>
                                <td class="ef-table__actions">
                                    <div class="ef-actions">
                                        <a class="ef-button ef-button--ghost" href="{{ route('admin.payments.show', $payment) }}">Details</a>
                                        @unless ($payment->isSuccessful())
                                            <form method="POST" action="{{ route('admin.payments.reverify', $payment) }}">
                                                @csrf
                                                <button class="ef-button ef-button--secondary" type="submit">Re-check</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ef-panel__footer">
                <p class="ef-meta">Showing {{ $payments->firstItem() }}&ndash;{{ $payments->lastItem() }} of {{ $payments->total() }}</p>
                <x-pagination :paginator="$payments" />
            </div>
        @endif
    </section>
@endsection
