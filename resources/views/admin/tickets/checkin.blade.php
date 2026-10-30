@extends('layouts.admin')
@section('title', 'Check-in')
@section('eyebrow', 'Admissions')
@section('actions')
    <a class="ef-button ef-button--secondary" href="{{ route('admin.tickets.checkin.index') }}">Switch event</a>
@endsection

@section('content')
    @php
        $issued = (int) $event->issued_count;
        $checkedIn = (int) $event->checked_in_count;
        $outstanding = max(0, $issued - $checkedIn);
        $attendanceRate = $issued > 0 ? round($checkedIn / $issued * 100, 1) : 0.0;

        // Rendered so a scan still reports its outcome without JavaScript, and
        // so a page reload never silently loses the last result.
        $scan = session('scan');
    @endphp

    <section class="ef-checkin-console" data-checkin-root data-checkin-url="{{ route('admin.tickets.checkin.process', $event) }}" data-verify-url="{{ route('admin.tickets.checkin.verify', $event) }}">
        <div class="ef-checkin-console__main">
            <section class="ef-panel" aria-labelledby="scan-heading">
                <header class="ef-panel__header">
                    <div>
                        <p class="ef-eyebrow">{{ $event->event_date->format('l, F j') }} @if ($event->startTimeLabel()) &middot; {{ $event->startTimeLabel() }} @endif</p>
                        <h2 class="ef-heading-md" id="scan-heading">{{ $event->title }}</h2>
                        <p class="ef-meta">{{ $event->addressLine() ?? $event->location }}</p>
                    </div>
                </header>

                <div class="ef-checkin-console__scanner" id="checkin-scanner" data-ticket-scanner></div>

                <form class="ef-checkin-console__form" method="POST" action="{{ route('admin.tickets.checkin.process', $event) }}" data-checkin-form>
                    @csrf

                    <div class="ef-field">
                        <label class="ef-label" for="code">Ticket code</label>
                        <p class="ef-field__hint">Scan the attendee's QR code, or type the ticket number printed on their ticket.</p>
                        <div class="ef-checkin-console__input-row">
                            <input
                                class="ef-input"
                                id="code"
                                name="code"
                                type="text"
                                required
                                autofocus
                                autocomplete="off"
                                autocapitalize="characters"
                                spellcheck="false"
                                inputmode="text"
                                data-ticket-token-input
                                placeholder="EF-XXXXXXXXXXXX or scan"
                            >
                            <x-ui.button type="submit">Check in</x-ui.button>
                        </div>
                    </div>
                </form>

                <div class="ef-scan-result" data-checkin-result aria-live="polite" @if (! $scan) hidden @endif>
                    <div class="ef-scan-result__panel" data-tone="{{ $scan['tone'] ?? 'neutral' }}">
                        <p class="ef-scan-result__state" data-checkin-state>{{ $scan['message'] ?? '' }}</p>
                        <p class="ef-scan-result__detail" data-checkin-detail>
                            @if ($scan['attendee'] ?? null)
                                {{ $scan['attendee'] }}@if ($scan['checked_in_at'] ?? null) &middot; {{ $scan['checked_in_at'] }} @endif
                            @elseif ($scan ?? null)
                                No matching ticket for this event.
                            @endif
                        </p>
                        <dl class="ef-scan-result__facts" data-checkin-facts @if (! ($scan['ticket_number'] ?? null)) hidden @endif>
                            <div>
                                <dt>Ticket</dt>
                                <dd data-checkin-ticket>{{ $scan['ticket_number'] ?? '' }}</dd>
                            </div>
                            <div>
                                <dt>Time</dt>
                                <dd data-checkin-time>{{ $scan['checked_in_at'] ?? '' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <p class="ef-checkin-console__offline-note" data-checkin-status aria-live="polite"></p>
            </section>

            <section class="ef-panel" aria-labelledby="manual-heading">
                <header class="ef-panel__header">
                    <h2 class="ef-heading-md" id="manual-heading">Manual lookup</h2>
                    <p class="ef-meta">Confirm a ticket before letting anyone in, without recording a check-in.</p>
                </header>

                <form class="ef-inline-form" data-checkin-verify-form>
                    <div class="ef-field">
                        <label class="ef-label" for="verify-code">Ticket number or token</label>
                        <input class="ef-input" id="verify-code" type="text" name="code" autocomplete="off" spellcheck="false" data-verify-input>
                    </div>
                    <x-ui.button type="submit" variant="secondary">Verify</x-ui.button>
                </form>

                <div class="ef-scan-result" data-verify-result aria-live="polite" hidden>
                    <div class="ef-scan-result__panel" data-tone="neutral">
                        <p class="ef-scan-result__state" data-verify-state></p>
                        <p class="ef-scan-result__detail" data-verify-detail></p>
                    </div>
                </div>
            </section>
        </div>

        <aside class="ef-checkin-console__side">
            <section class="ef-panel ef-checkin-stats" aria-labelledby="stats-heading">
                <header class="ef-panel__header">
                    <h2 class="ef-heading-md" id="stats-heading">Door progress</h2>
                </header>

                <dl class="ef-checkin-stats__grid">
                    <div>
                        <dt>Checked in</dt>
                        <dd data-checkin-count>{{ $checkedIn }}</dd>
                    </div>
                    <div>
                        <dt>Outstanding</dt>
                        <dd data-checkin-outstanding>{{ $outstanding }}</dd>
                    </div>
                    <div>
                        <dt>Issued</dt>
                        <dd>{{ $issued }}</dd>
                    </div>
                    <div>
                        <dt>Attendance</dt>
                        <dd>{{ $attendanceRate }}%</dd>
                    </div>
                </dl>

                <div
                    class="ef-progress"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="{{ (int) $attendanceRate }}"
                    aria-label="Attendance rate"
                >
                    <span class="ef-progress__bar" data-checkin-progress style="--ef-progress-value: {{ $attendanceRate }}%"></span>
                </div>
            </section>

            <section class="ef-panel" aria-labelledby="recent-heading">
                <header class="ef-panel__header">
                    <h2 class="ef-heading-md" id="recent-heading">Recent arrivals</h2>
                    <span class="ef-meta" data-checkin-recent-count>{{ $recentCheckIns->count() }}</span>
                </header>

                <ul class="ef-list ef-list--compact" data-checkin-recent>
                    @forelse ($recentCheckIns as $ticket)
                        <li class="ef-list__item">
                            <div>
                                <p class="ef-list__title">{{ $ticket->registration?->attendeeName() }}</p>
                                <p class="ef-list__meta">
                                    {{ $ticket->displayNumber() }}
                                    @if ($ticket->checked_in_at) &middot; {{ $ticket->checked_in_at->format('g:i A') }} @endif
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="ef-list__item">
                            <p class="ef-meta">Nobody has arrived yet.</p>
                        </li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </section>
@endsection
