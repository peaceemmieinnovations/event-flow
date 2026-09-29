@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="ef-dashboard" data-scroll-section>
        <div class="ef-container">
            <div class="ef-dashboard__header" data-reveal>
                <p class="ef-eyebrow">Your EventFlow</p>
                <h1 class="ef-heading-xl">Welcome, {{ $user->name }}.</h1>
                <p class="ef-lead">Your account is ready for the experiences ahead.</p>
            </div>

            <dl class="ef-dashboard__account" data-reveal>
                <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>Role</dt><dd>{{ str_replace('_', ' ', ucfirst($user->role)) }}</dd></div>
            </dl>

            <div class="ef-dashboard__sections" data-reveal>
                @if ($user->hasAnyRole([\App\Models\User::ROLE_SUPER_ADMIN, \App\Models\User::ROLE_ADMIN]))
                    @foreach (['Administration', 'Users', 'Platform management'] as $section)
                        <article><p class="ef-eyebrow">Coming next</p><h2 class="ef-heading-md">{{ $section }}</h2><p class="ef-body">This workspace will be available as EventFlow grows.</p></article>
                    @endforeach
                    <article><p class="ef-eyebrow">Event management</p><h2 class="ef-heading-md">Events</h2><p class="ef-body">Review the program and prepare what comes next.</p><a class="ef-link" href="{{ route('admin.events.index') }}">Manage events</a></article>
                @elseif ($user->hasRole(\App\Models\User::ROLE_ORGANIZER))
                    @foreach (['My events', 'Create event', 'Event management'] as $section)
                        <article><p class="ef-eyebrow">Coming next</p><h2 class="ef-heading-md">{{ $section }}</h2><p class="ef-body">Your organizer tools will appear here.</p></article>
                    @endforeach
                @else
                    @foreach (['Upcoming events', 'Tickets', 'Favorites'] as $section)
                        <article><p class="ef-eyebrow">Coming next</p><h2 class="ef-heading-md">{{ $section }}</h2><p class="ef-body">Nothing to show yet. Explore EventFlow to find what is next.</p></article>
                    @endforeach
                @endif
            </div>
        </div>
    </section>
@endsection
