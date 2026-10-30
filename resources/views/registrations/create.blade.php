@extends('layouts.app')
@section('title', 'Register for '.$event->title)
@section('content')
    <section class="ef-register" data-scroll-section>
        <div class="ef-container ef-register__grid">
            <aside class="ef-register__summary">
                <p class="ef-eyebrow">Register</p>
                <h1 class="ef-heading-xl">{{ $event->title }}</h1>
                <p class="ef-lead">{{ $event->event_date->format('l, j F Y') }}</p>

                <dl class="ef-register__facts">
                    <div>
                        <dt>Time</dt>
                        <dd>
                            @if ($event->startTimeLabel())
                                {{ $event->startTimeLabel() }}@if ($event->endTimeLabel()) &ndash; {{ $event->endTimeLabel() }} @endif
                            @else
                                To be confirmed
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>Venue</dt>
                        <dd>{{ $event->addressLine() ?? $event->location }}</dd>
                    </div>
                    <div>
                        <dt>Ticket</dt>
                        <dd>{{ $event->priceLabel() }}</dd>
                    </div>
                    @if ($spotsRemaining !== null)
                        <div>
                            <dt>Places left</dt>
                            <dd>{{ $spotsRemaining }}</dd>
                        </div>
                    @endif
                </dl>

                @if (! $event->isFree())
                    <p class="ef-register__note">
                        You will be taken to our payment partner to complete the purchase. Your ticket is issued only after
                        the payment is verified on our servers.
                    </p>
                @endif

                <a class="ef-link" href="{{ route('events.show', $event) }}">&larr; Back to event</a>
            </aside>

            <form class="ef-form ef-form--narrow" method="POST" action="{{ route('registrations.store', $event) }}" novalidate>
                @csrf

                <x-flash />

                <div class="ef-form__grid">
                    <div class="ef-field">
                        <label class="ef-label" for="first_name">First name</label>
                        <input class="ef-input" id="first_name" name="first_name" type="text" required maxlength="100" autocomplete="given-name" value="{{ old('first_name', auth()->user()?->name ? explode(' ', auth()->user()->name)[0] : '') }}">
                        @error('first_name')<p class="ef-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="ef-field">
                        <label class="ef-label" for="last_name">Last name</label>
                        <input class="ef-input" id="last_name" name="last_name" type="text" required maxlength="100" autocomplete="family-name" value="{{ old('last_name') }}">
                        @error('last_name')<p class="ef-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="ef-field">
                        <label class="ef-label" for="email">Email</label>
                        <input class="ef-input" id="email" name="email" type="email" required maxlength="255" autocomplete="email" value="{{ old('email', auth()->user()?->email) }}">
                        <p class="ef-field__hint">Your confirmation and ticket go here.</p>
                        @error('email')<p class="ef-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="ef-field">
                        <label class="ef-label" for="phone">Phone</label>
                        <input class="ef-input" id="phone" name="phone" type="tel" required maxlength="40" autocomplete="tel" value="{{ old('phone') }}">
                        <p class="ef-field__hint">Used only for urgent updates on the day.</p>
                        @error('phone')<p class="ef-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="ef-field ef-field--wide">
                        <label class="ef-label" for="organization">Organization <span class="ef-field__optional">optional</span></label>
                        <input class="ef-input" id="organization" name="organization" type="text" maxlength="255" autocomplete="organization" value="{{ old('organization') }}">
                        @error('organization')<p class="ef-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="ef-field ef-field--wide">
                        <label class="ef-label" for="notes">Anything we should know? <span class="ef-field__optional">optional</span></label>
                        <textarea class="ef-textarea" id="notes" name="notes" rows="5" maxlength="2000" placeholder="Access needs, dietary requirements, or questions for the organizer.">{{ old('notes') }}</textarea>
                        @error('notes')<p class="ef-field__error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="ef-form__actions">
                    <x-ui.button type="submit">
                        {{ $event->isFree() ? 'Complete registration' : 'Continue to payment · '.$event->priceLabel() }}
                    </x-ui.button>
                    <a class="ef-button ef-button--secondary" href="{{ route('events.show', $event) }}">Cancel</a>
                </div>

                <p class="ef-meta">
                    By registering you agree to receive transactional emails about this event. One registration per email address.
                </p>
            </form>
        </div>
    </section>
@endsection
