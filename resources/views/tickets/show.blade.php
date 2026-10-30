@extends('layouts.app')
@section('title', 'Your ticket')
@section('content')
    <section class="ef-ticket-page" data-scroll-section>
        <div class="ef-container ef-container--narrow">
            <p class="ef-eyebrow">EventFlow ticket</p>
            <h1 class="ef-heading-xl">Your ticket is ready</h1>
            <p class="ef-lead">Present the QR code below at the door. You can also print it for a physical copy.</p>

            @include('tickets.partials.card', ['ticket' => $ticket, 'mode' => 'screen'])

            <div class="ef-ticket-page__actions">
                <x-ui.button :href="route('tickets.print', $ticket->token)">Print ticket</x-ui.button>
                <a class="ef-button ef-button--secondary" href="{{ route('events.show', $ticket->event) }}">View event</a>
                <a class="ef-button ef-button--ghost" href="{{ route('home') }}">Back to home</a>
            </div>

            <p class="ef-meta ef-ticket-page__note">
                Anyone with this link can view this ticket, so treat it like a boarding pass. If you believe it has been shared, ask the organizer to reissue it.
            </p>
        </div>
    </section>
@endsection
