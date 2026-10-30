@extends('layouts.admin')
@section('title', 'Compose message')
@section('eyebrow', 'Messaging')
@section('actions')
    <a class="ef-button ef-button--secondary" href="{{ route('admin.communications.index') }}">Back to communications</a>
@endsection

@section('content')
    <form class="ef-form ef-form--narrow" method="POST" action="{{ route('admin.communications.store') }}">
        @csrf

        <section class="ef-panel" aria-labelledby="compose-heading">
            <header class="ef-panel__header">
                <h2 class="ef-heading-md" id="compose-heading">New broadcast</h2>
                <p class="ef-meta">Each message is queued individually, so one bad address never blocks the rest of the audience.</p>
            </header>

            <div class="ef-form__grid">
                <div class="ef-field">
                    <label class="ef-label" for="event_id">Event</label>
                    <select class="ef-select" id="event_id" name="event_id" required>
                        <option value="">Choose an event</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" @selected($selectedEvent === $event->id)>{{ $event->title }} &middot; {{ $event->event_date->format('M j, Y') }}</option>
                        @endforeach
                    </select>
                    @error('event_id')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field">
                    <label class="ef-label" for="audience">Audience</label>
                    <select class="ef-select" id="audience" name="audience" required>
                        @foreach ($audiences as $key => $label)
                            <option value="{{ $key }}" @selected(old('audience', 'all') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="ef-field__hint">Cancelled registrations are never contacted.</p>
                    @error('audience')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field ef-field--wide">
                    <label class="ef-label" for="subject">Subject</label>
                    <input class="ef-input" id="subject" name="subject" type="text" required maxlength="150" value="{{ old('subject') }}" placeholder="Doors open at 6pm sharp">
                    @error('subject')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>

                <div class="ef-field ef-field--wide">
                    <label class="ef-label" for="message">Message</label>
                    <textarea class="ef-textarea" id="message" name="message" rows="8" required maxlength="5000" placeholder="Share the practical details attendees need.">{{ old('message') }}</textarea>
                    <p class="ef-field__hint">Keep it plain text. Links are preserved as written.</p>
                    @error('message')<p class="ef-field__error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="ef-form__actions">
            <x-ui.button type="submit">Queue broadcast</x-ui.button>
            <a class="ef-button ef-button--secondary" href="{{ route('admin.communications.index') }}">Cancel</a>
        </div>
    </form>
@endsection
