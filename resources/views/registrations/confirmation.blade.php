@extends('layouts.app')
@section('title', $registration->isPending() ? 'Awaiting payment' : 'Registration confirmed')
@section('content')
    <section class="ef-confirmation" data-scroll-section>
        <div class="ef-container ef-container--narrow">
            <div class="ef-confirmation__hero" data-tone="{{ $registration->isPending() ? 'warning' : 'success' }}">
                <p class="ef-eyebrow">{{ $registration->isPending() ? 'Registration received' : 'Registration confirmed' }}</p>
                <h1 class="ef-heading-xl">
                    {{ $registration->isPending() ? 'One last step: complete your payment.' : 'You are on the list.' }}
                </h1>
                <p class="ef-lead">
                    {{ $registration->attendeeName() }}, your place for
                    <strong>{{ $registration->event->title }}</strong>
                    {{ $registration->isPending() ? 'is held while we wait for the payment gateway to confirm.' : 'is confirmed.' }}
                </p>
            </div>

            <dl class="ef-confirmation__facts">
                <div>
                    <dt>Reference</dt>
                    <dd><code class="ef-code">{{ $registration->reference }}</code></dd>
                </div>
                <div>
                    <dt>Event</dt>
                    <dd><a class="ef-link" href="{{ route('events.show', $registration->event) }}">{{ $registration->event->title }}</a></dd>
                </div>
                <div>
                    <dt>When</dt>
                    <dd>
                        {{ $registration->event->event_date->format('l, j F Y') }}
                        @if ($registration->event->startTimeLabel())
                            &middot; {{ $registration->event->startTimeLabel() }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Where</dt>
                    <dd>{{ $registration->event->addressLine() ?? $registration->event->location }}</dd>
                </div>
                <div>
                    <dt>Attendee</dt>
                    <dd>{{ $registration->email }}</dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd><x-badge :label="$registration->status" /></dd>
                </div>
            </dl>

            @if ($registration->isPending())
                <section class="ef-confirmation__panel" aria-labelledby="payment-heading">
                    <h2 class="ef-heading-md" id="payment-heading">Payment</h2>

                    @if ($payment)
                        <p class="ef-body">
                            {{ $payment->formattedAmountWithCode() }} &middot; reference
                            <code class="ef-code">{{ $payment->reference }}</code>
                        </p>
                        <p class="ef-meta">Status: <x-badge :label="$payment->status" /></p>

                        @if ($payment->canBeRetried())
                            <form method="POST" action="{{ route('payments.retry', $payment) }}">
                                @csrf
                                <x-ui.button type="submit">Try payment again</x-ui.button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('payments.retry', $payment) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary">Resume checkout</x-ui.button>
                            </form>
                            <p class="ef-meta">
                                If you completed checkout but still see this page, refresh in a moment. The gateway may
                                be finishing its notification.
                            </p>
                        @endif
                    @else
                        <x-empty title="Preparing checkout" copy="We are setting up your payment session. This usually takes a second." />
                    @endif

                    <form method="POST" action="{{ route('registrations.resend', $registration) }}" class="ef-confirmation__resend">
                        @csrf
                        <button class="ef-link" type="submit">Resend my confirmation email</button>
                    </form>
                </section>
            @else
                <section class="ef-confirmation__panel" aria-labelledby="next-heading">
                    <h2 class="ef-heading-md" id="next-heading">What happens next</h2>
                    <ol class="ef-steps">
                        <li>Your confirmation and ticket have been emailed to {{ $registration->email }}.</li>
                        <li>Bring the QR code on your ticket to the door &mdash; staff scan it to check you in.</li>
                        <li>Add the event to your calendar so you do not miss it.</li>
                    </ol>

                    <div class="ef-confirmation__actions">
                        @if ($registration->ticket)
                            <x-ui.button :href="route('tickets.show', $registration->ticket->token)">View ticket</x-ui.button>
                            <a class="ef-button ef-button--secondary" href="{{ route('tickets.print', $registration->ticket->token) }}">Print ticket</a>
                        @endif
                        <a class="ef-button ef-button--ghost" href="{{ route('events.show', $registration->event) }}">Back to event</a>
                    </div>

                    <form method="POST" action="{{ route('registrations.resend', $registration) }}" class="ef-confirmation__resend">
                        @csrf
                        <button class="ef-link" type="submit">Resend my confirmation email</button>
                    </form>
                </section>
            @endif
        </div>
    </section>
@endsection
