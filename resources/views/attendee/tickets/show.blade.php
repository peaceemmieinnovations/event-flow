@extends('layouts.app')
@section('title', 'Ticket '.$ticket->displayNumber())
@section('content')
    <section class="ef-ticket-page" data-scroll-section>
        <div class="ef-container ef-container--narrow">
            <p class="ef-eyebrow"><a class="ef-link" href="{{ route('attendee.tickets') }}">&larr; My tickets</a></p>
            <h1 class="ef-heading-xl">{{ $ticket->event?->title }}</h1>

            @include('tickets.partials.card', ['ticket' => $ticket, 'mode' => 'screen'])

            <div class="ef-ticket-page__actions">
                <x-ui.button :href="route('tickets.print', $ticket->token)">Print ticket</x-ui.button>
                <a class="ef-button ef-button--secondary" href="{{ route('events.show', $ticket->event) }}">View event</a>
                <a class="ef-button ef-button--ghost" href="{{ route('attendee.tickets') }}">All tickets</a>
            </div>
        </div>
    </section>
@endsection
