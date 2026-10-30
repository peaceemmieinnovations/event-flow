@php
    $registration = $ticket->registration;
    $event = $ticket->event;
    $isInvalid = $ticket->isCancelled() || $registration?->isCancelled();
    $isUsed = $ticket->isCheckedIn();
@endphp

<article class="ef-ticket {{ $isInvalid || $isUsed ? 'ef-ticket--spent' : '' }}" aria-label="EventFlow ticket for {{ $event?->title }}">
    <header class="ef-ticket__header">
        <div>
            <p class="ef-eyebrow">EventFlow ticket</p>
            <h1 class="ef-ticket__title">{{ $event?->title }}</h1>
        </div>
        <span class="ef-ticket__status" data-tone="{{ $isInvalid ? 'danger' : ($isUsed ? 'neutral' : 'success') }}">
            {{ $isInvalid ? 'Cancelled' : ($isUsed ? 'Checked in' : 'Valid') }}
        </span>
    </header>

    <div class="ef-ticket__body">
        <div class="ef-ticket__details">
            <dl class="ef-ticket__facts">
                <div>
                    <dt>Attendee</dt>
                    <dd>{{ $registration?->attendeeName() }}</dd>
                </div>
                <div>
                    <dt>Date</dt>
                    <dd>{{ $event?->event_date->format('l, j F Y') }}</dd>
                </div>
                <div>
                    <dt>Time</dt>
                    <dd>
                        @if ($event?->startTimeLabel())
                            {{ $event->startTimeLabel() }}@if ($event->endTimeLabel()) &ndash; {{ $event->endTimeLabel() }} @endif
                        @else
                            Time to be confirmed
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Venue</dt>
                    <dd>{{ $event?->addressLine() ?? $event?->location }}</dd>
                </div>
                <div>
                    <dt>Ticket number</dt>
                    <dd><strong class="ef-ticket__number">{{ $ticket->displayNumber() }}</strong></dd>
                </div>
                <div>
                    <dt>Reference</dt>
                    <dd>{{ $registration?->reference }}</dd>
                </div>
            </dl>
        </div>

        <div class="ef-ticket__code">
            <div class="ef-ticket__qr" role="img" aria-label="QR code for ticket {{ $ticket->displayNumber() }}">
                <canvas
                    data-ticket-qr
                    data-token="{{ $ticket->token }}"
                    width="220"
                    height="220"
                    aria-hidden="true"
                ></canvas>
                <noscript>
                    <p class="ef-ticket__qr-fallback">
                        Show ticket number <strong>{{ $ticket->displayNumber() }}</strong> at the door.
                    </p>
                </noscript>
            </div>

            <p class="ef-ticket__code-label">Present this code at the door</p>
            <p class="ef-ticket__code-value">{{ $ticket->displayNumber() }}</p>
        </div>
    </div>

    @if ($isUsed)
        <p class="ef-ticket__footnote">
            Checked in
            @if ($ticket->checked_in_at)
                at {{ $ticket->checked_in_at->format('j M Y, g:i A') }}
            @endif
            @if ($ticket->checkedInBy)
                by {{ $ticket->checkedInBy->name }}
            @endif
            . This ticket has already been used.
        </p>
    @elseif ($isInvalid)
        <p class="ef-ticket__footnote">This ticket is no longer valid. Contact the organizer if you think that is wrong.</p>
    @else
        <p class="ef-ticket__footnote">Keep this page open at the door, or print it from the button above.</p>
    @endif
</article>
