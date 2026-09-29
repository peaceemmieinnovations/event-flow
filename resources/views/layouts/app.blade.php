<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    $isHome = request()->routeIs('home');
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') &middot; @endif EventFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class(['ef-page--home' => $isHome, 'ef-page--standard' => ! $isHome])>
    <a class="ef-skip-link" href="#main-content">Skip to content</a>

    <header @class(['ef-site-header', 'ef-site-header--overlay' => $isHome]) data-site-header>
        <div class="ef-container ef-site-header__inner">
            <a class="ef-brand" href="{{ route('home') }}" aria-label="EventFlow home">
                <span class="ef-brand__mark" aria-hidden="true"></span>
                <span>EventFlow</span>
            </a>

            <nav class="ef-navigation ef-navigation--desktop" aria-label="Primary navigation">
                <div class="ef-navigation__primary">
                    <a class="ef-navigation__link" href="{{ route('events.index') }}" @if (request()->routeIs('events.*')) aria-current="page" @endif>Explore</a>
                    <a class="ef-navigation__link" href="{{ $isHome ? '#categories' : route('events.index') }}" aria-label="Browse event categories">Categories</a>
                    <a class="ef-navigation__link" href="{{ $isHome ? '#discover' : route('events.index') }}" @if ($isHome) aria-current="page" @endif>Discover</a>
                </div>

                <div class="ef-navigation__utilities">
                    <a class="ef-navigation__search" href="{{ route('events.index') }}" aria-label="Browse events">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                            <circle cx="11" cy="11" r="5.75"></circle>
                            <path d="m16 16 4 4"></path>
                        </svg>
                        <span>Search</span>
                    </a>

                    @guest
                        <a class="ef-navigation__link ef-navigation__link--utility" href="{{ route('login') }}">Log in</a>
                        <x-ui.button :href="route('register')" class="ef-navigation__cta">Create account</x-ui.button>
                    @else
                        <a class="ef-navigation__link ef-navigation__link--utility" href="{{ route('dashboard') }}">Dashboard</a>
                        @if (auth()->user()->hasAnyRole([\App\Models\User::ROLE_ADMIN]))
                            <a class="ef-navigation__link ef-navigation__link--utility" href="{{ route('admin.events.index') }}" @if (request()->routeIs('admin.events.*')) aria-current="page" @endif>Events</a>
                            <a class="ef-navigation__link ef-navigation__link--utility" href="{{ route('admin.registrations.index') }}" @if (request()->routeIs('admin.registrations.*')) aria-current="page" @endif>Registrations</a>
                        @endif
                        <details class="ef-account-menu">
                            <summary>{{ auth()->user()->isSuperAdmin() ? 'Super Admin' : auth()->user()->name }}</summary>
                            <div class="ef-account-menu__panel">
                                <p>{{ auth()->user()->email }}</p>
                                <form method="POST" action="{{ route('logout') }}">@csrf <button type="submit">Log out</button></form>
                            </div>
                        </details>
                    @endguest
                </div>
            </nav>

            <button class="ef-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-navigation" aria-label="Open navigation menu">
                <span class="ef-menu-toggle__line" aria-hidden="true"></span>
                <span class="ef-menu-toggle__line" aria-hidden="true"></span>
                <span class="ef-sr-only">Menu</span>
            </button>
        </div>

        <div class="ef-mobile-menu" id="mobile-navigation" data-mobile-menu role="dialog" aria-modal="true" aria-label="Mobile navigation" aria-hidden="true" hidden inert>
            <div class="ef-mobile-menu__panel" data-mobile-menu-panel>
                <div class="ef-mobile-menu__header">
                    <p class="ef-eyebrow">Navigate</p>
                    <button class="ef-mobile-menu__close" type="button" data-mobile-menu-close>Close</button>
                </div>

                <nav class="ef-mobile-menu__links" aria-label="Mobile primary navigation">
                    <a href="{{ route('events.index') }}" data-mobile-menu-link>Explore</a>
                    <a href="{{ $isHome ? '#categories' : route('events.index') }}" data-mobile-menu-link>Categories</a>
                    <a href="{{ $isHome ? '#discover' : route('events.index') }}" data-mobile-menu-link>Discover</a>
                </nav>

                <div class="ef-mobile-menu__utilities">
                    <a class="ef-mobile-menu__search" href="{{ route('events.index') }}" data-mobile-menu-link>
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                            <circle cx="11" cy="11" r="5.75"></circle>
                            <path d="m16 16 4 4"></path>
                        </svg>
                        <span>Search events</span>
                    </a>

                    @guest
                        <a class="ef-mobile-menu__login" href="{{ route('login') }}" data-mobile-menu-link>Log in</a>
                        <x-ui.button :href="route('register')" class="ef-mobile-menu__cta" data-mobile-menu-link>Create account</x-ui.button>
                    @else
                        <a class="ef-mobile-menu__login" href="{{ route('dashboard') }}" data-mobile-menu-link>Dashboard</a>
                        @if (auth()->user()->hasAnyRole([\App\Models\User::ROLE_ADMIN]))
                            <a class="ef-mobile-menu__login" href="{{ route('admin.events.index') }}" data-mobile-menu-link>Events</a>
                            <a class="ef-mobile-menu__login" href="{{ route('admin.registrations.index') }}" data-mobile-menu-link>Registrations</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">@csrf <button class="ef-button ef-button--primary ef-mobile-menu__cta" type="submit">Log out</button></form>
                    @endguest
                </div>
            </div>
        </div>
    </header>

    <main id="main-content" data-scroll-section tabindex="-1">
        @yield('content')
    </main>

</body>
</html>
