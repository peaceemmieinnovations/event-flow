@extends('layouts.app')
@section('title', 'Payment not completed')
@section('content')
    <section class="ef-confirmation" data-scroll-section>
        <div class="ef-container ef-container--narrow">
            <div class="ef-confirmation__hero" data-tone="warning">
                <p class="ef-eyebrow">Payment incomplete</p>
                <h1 class="ef-heading-xl">We could not confirm this payment yet.</h1>
                <p class="ef-lead">
                    Your place for <strong>{{ $payment->event?->title }}</strong> is still held, so you can safely try again.
                </p>
            </div>

            @if ($reason === 'unavailable')
                <p class="ef-alert" data-tone="warning" role="status">
                    The payment gateway did not respond, so nothing was charged. This is usually temporary.
                </p>
            @else
                <p class="ef-alert" data-tone="danger" role="status">
                    The gateway returned details that do not match this booking, so we have not confirmed anything.
                    Contact the organizer with your registration reference {{ $payment->registration?->reference }}.
                </p>
            @endif

            <section class="ef-confirmation__panel" aria-labelledby="retry-heading">
                <h2 class="ef-heading-md" id="retry-heading">Your booking</h2>

                <dl class="ef-confirmation__facts">
                    <div>
                        <dt>Registration</dt>
                        <dd><code class="ef-code">{{ $payment->registration?->reference }}</code></dd>
                    </div>
                    <div>
                        <dt>Amount</dt>
                        <dd>{{ $payment->formattedAmountWithCode() }}</dd>
                    </div>
                    <div>
                        <dt>Payment reference</dt>
                        <dd><code class="ef-code">{{ $payment->reference }}</code></dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd><x-badge :label="$payment->status" /></dd>
                    </div>
                </dl>

                <div class="ef-confirmation__actions">
                    <form method="POST" action="{{ route('payments.retry', $payment) }}">
                        @csrf
                        <x-ui.button type="submit">Try payment again</x-ui.button>
                    </form>
                    <a class="ef-button ef-button--secondary" href="{{ route('events.show', $payment->event) }}">Back to event</a>
                </div>
            </section>
        </div>
    </section>
@endsection
