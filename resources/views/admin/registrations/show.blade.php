@extends('layouts.admin')
@section('title', $registration->attendeeName())
@section('eyebrow', 'Registration')
@section('actions')
    <a class="ef-button ef-button--secondary" href="{{ route('admin.registrations.index') }}">Back to registrations</a>
    @if ($registration->ticket)
        <a class="ef-button ef-button--primary" href="{{ route('tickets.show', $registration->ticket->token) }}">View ticket</a>
    @endif
@endsection

@section('content')
    <section class="ef-registration-summary" aria-label="Registration summary">
        <div class="ef-registration-summary__identity">
            <span class="ef-admin-avatar ef-admin-avatar--large" aria-hidden="true">{{ $registration->user?->initials() ?? mb_substr($registration->first_name, 0, 1) }}</span>
            <div>
                <h2 class="ef-heading-lg">{{ $registration->attendeeName() }}</h2>
                <p class="ef-meta">{{ $registration->email }}@if ($registration->phone) &middot; {{ $registration->phone }}@endif</p>
            </div>
        </div>

        <dl class="ef-definition-grid">
            <div>
                <dt>Reference</dt>
                <dd><code class="ef-code">{{ $registration->reference }}</code></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd><x-badge :label="$registration->status" /></dd>
            </div>
            <div>
                <dt>Event</dt>
                <dd><a class="ef-link" href="{{ route('admin.events.analytics', $registration->event) }}">{{ $registration->event->title }}</a></dd>
            </div>
            <div>
                <dt>Registered</dt>
                <dd>{{ $registration->registered_at?->format('D, M j, Y g:i A') }}</dd>
            </div>
            <div>
                <dt>Ticket</dt>
                <dd>
                    @if ($registration->ticket)
                        <code class="ef-code">{{ $registration->ticket->displayNumber() }}</code>
                        <x-badge :label="$registration->ticket->status" />
                    @else
                        <span class="ef-meta">Not issued</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Checked in</dt>
                <dd>{{ $registration->ticket?->checked_in_at?->format('D, M j, g:i A') ?? '—' }}</dd>
            </div>
            @if ($registration->organization)
                <div>
                    <dt>Organization</dt>
                    <dd>{{ $registration->organization }}</dd>
                </div>
            @endif
        </dl>

        @if ($registration->notes)
            <div class="ef-note">
                <p class="ef-label">Attendee notes</p>
                <p>{{ $registration->notes }}</p>
            </div>
        @endif
    </section>

    <div class="ef-admin-grid ef-admin-grid--split">
        <section class="ef-panel" aria-labelledby="payments-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="payments-heading">Payments</h2>
            </header>

            @if ($registration->payments->isEmpty())
                <x-empty title="No payment needed" copy="This is a free event, so no payment record was created." />
            @else
                <ul class="ef-list">
                    @foreach ($registration->payments as $payment)
                        <li class="ef-list__item">
                            <div>
                                <p class="ef-list__title"><code class="ef-code">{{ $payment->reference }}</code></p>
                                <p class="ef-list__meta">
                                    {{ $payment->formattedAmountWithCode() }}
                                    · {{ $payment->paid_at?->format('M j, Y g:i A') ?? 'Not completed' }}
                                    @if ($payment->gateway_transaction_id)
                                        · txn {{ $payment->gateway_transaction_id }}
                                    @endif
                                </p>
                            </div>
                            <x-badge :label="$payment->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="ef-panel" aria-labelledby="communications-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="communications-heading">Communication history</h2>
            </header>

            @if ($registration->communicationLogs->isEmpty())
                <x-empty title="Nothing sent yet" copy="Confirmations, receipts and reminders all appear here once they are queued." />
            @else
                <ul class="ef-list">
                    @foreach ($registration->communicationLogs as $log)
                        <li class="ef-list__item">
                            <div>
                                <p class="ef-list__title">{{ $log->subject }}</p>
                                <p class="ef-list__meta">
                                    {{ str_replace('_', ' ', $log->type) }}
                                    · {{ $log->created_at->format('M j, Y g:i A') }}
                                    @if ($log->error_message)
                                        · {{ $log->error_message }}
                                    @endif
                                </p>
                            </div>
                            <x-badge :label="$log->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <section class="ef-panel" aria-labelledby="status-heading">
        <header class="ef-panel__header">
            <h2 class="ef-heading-md" id="status-heading">Change status</h2>
            <p class="ef-meta">Cancelling invalidates the ticket and emails the attendee.</p>
        </header>

        <form class="ef-inline-form" method="POST" action="{{ route('admin.registrations.status', $registration) }}">
            @csrf
            @method('PATCH')

            <div class="ef-field">
                <label class="ef-label" for="registration-status">New status</label>
                <select class="ef-select" id="registration-status" name="status" required>
                    @foreach (\App\Models\Registration::STATUSES as $status)
                        <option value="{{ $status }}" @selected($registration->status === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <x-ui.button type="submit" variant="secondary">Update status</x-ui.button>
        </form>
    </section>
@endsection
