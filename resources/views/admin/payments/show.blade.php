@extends('layouts.admin')
@section('title', 'Payment details')
@section('eyebrow', 'Revenue')
@section('actions')
    <a class="ef-button ef-button--secondary" href="{{ route('admin.payments.index') }}">Back to payments</a>
@endsection

@section('content')
    <section class="ef-registration-summary" aria-label="Payment summary">
        <div class="ef-registration-summary__identity">
            <div>
                <p class="ef-eyebrow">Payment reference</p>
                <h2 class="ef-heading-lg"><code class="ef-code">{{ $payment->reference }}</code></h2>
                <p class="ef-meta">{{ $payment->formattedAmountWithCode() }} &middot; <x-badge :label="$payment->status" /></p>
            </div>
        </div>

        <dl class="ef-definition-grid">
            <div>
                <dt>Attendee</dt>
                <dd>
                    <a class="ef-link" href="{{ route('admin.registrations.show', $payment->registration) }}">
                        {{ $payment->registration?->attendeeName() }}
                    </a>
                    <span class="ef-meta">{{ $payment->registration?->email }}</span>
                </dd>
            </div>
            <div>
                <dt>Event</dt>
                <dd><a class="ef-link" href="{{ route('admin.events.analytics', $payment->event) }}">{{ $payment->event?->title }}</a></dd>
            </div>
            <div>
                <dt>Gateway</dt>
                <dd>{{ ucfirst($payment->gateway) }}</dd>
            </div>
            <div>
                <dt>Gateway transaction</dt>
                <dd>{{ $payment->gateway_transaction_id ?? '—' }}</dd>
            </div>
            <div>
                <dt>Created</dt>
                <dd>{{ $payment->created_at->format('D, M j, Y g:i A') }}</dd>
            </div>
            <div>
                <dt>Completed</dt>
                <dd>{{ $payment->paid_at?->format('D, M j, Y g:i A') ?? 'Not completed' }}</dd>
            </div>
            <div>
                <dt>Retryable</dt>
                <dd>{{ $payment->canBeRetried() ? 'Yes' : 'No' }}</dd>
            </div>
        </dl>
    </section>

    <div class="ef-admin-grid ef-admin-grid--split">
        <section class="ef-panel" aria-labelledby="metadata-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="metadata-heading">Gateway metadata</h2>
                <p class="ef-meta">Only non-sensitive fields are stored. Authorization URLs and raw provider payloads are discarded.</p>
            </header>

            @if (empty($payment->metadata))
                <x-empty title="No metadata stored" copy="Metadata is written when the gateway confirms or fails a transaction." />
            @else
                <dl class="ef-definition-grid ef-definition-grid--tight">
                    @foreach ($payment->metadata as $key => $value)
                        <div>
                            <dt>{{ ucwords(str_replace('_', ' ', (string) $key)) }}</dt>
                            <dd><code class="ef-code">{{ is_scalar($value) ? $value : json_encode($value) }}</code></dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </section>

        <section class="ef-panel" aria-labelledby="actions-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="actions-heading">Actions</h2>
            </header>

            <div class="ef-actions ef-actions--stacked">
                @unless ($payment->isSuccessful())
                    <form method="POST" action="{{ route('admin.payments.reverify', $payment) }}">
                        @csrf
                        <x-ui.button type="submit">Re-check with gateway</x-ui.button>
                    </form>
                    <p class="ef-meta">Asks Paystack for the current status. Settlement is idempotent, so a repeat can never issue a second ticket.</p>
                @else
                    <p class="ef-meta">This payment is already confirmed and its ticket has been issued.</p>
                @endunless

                @if ($payment->registration)
                    <a class="ef-button ef-button--secondary" href="{{ route('admin.registrations.show', $payment->registration) }}">View registration</a>
                @endif

                @if ($payment->registration?->ticket)
                    <a class="ef-button ef-button--secondary" href="{{ route('tickets.show', $payment->registration->ticket->token) }}">View ticket</a>
                @endif
            </div>
        </section>
    </div>
@endsection
