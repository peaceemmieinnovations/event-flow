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
                    <a class="ef-navigation__search" href="{{ route('events.index') }}" aria-label="Search events">
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
                        <a class="ef-navigation__link ef-navigation__link--utility" href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard', 'attendee.*')) aria-current="page" @endif>Dashboard</a>
                        @if (auth()->user()->isStaff())
                            <a class="ef-navigation__link ef-navigation__link--utility" href="{{ route('admin.dashboard') }}" @if (request()->routeIs('admin.*')) aria-current="page" @endif>Admin</a>
                        @endif
                        <details class="ef-account-menu">
                            <summary>{{ auth()->user()->isSuperAdmin() ? 'Super Admin' : auth()->user()->name }}</summary>
                            <div class="ef-account-menu__panel">
                                <p>{{ auth()->user()->email }}</p>
                                <ul class="ef-account-menu__links">
                                    <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li><a href="{{ route('attendee.tickets') }}">My tickets</a></li>
                                    @if (auth()->user()->isStaff())
                                        <li><a href="{{ route('admin.dashboard') }}">Admin workspace</a></li>
                                    @endif
                                </ul>
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
                    <a href="{{ route('events.index') }}" data-mobile-menu-link @if (request()->routeIs('events.*')) aria-current="page" @endif>Explore</a>
                    <a href="{{ route('events.index', ['when' => 'weekend']) }}" data-mobile-menu-link>This weekend</a>
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
                        <a class="ef-mobile-menu__login" href="{{ route('attendee.tickets') }}" data-mobile-menu-link>My tickets</a>
                        @if (auth()->user()->isStaff())
                            <a class="ef-mobile-menu__login" href="{{ route('admin.dashboard') }}" data-mobile-menu-link>Admin workspace</a>
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

    <footer class="ef-site-footer">
        <div class="ef-container">
            <div class="ef-site-footer__top">
                <div class="ef-site-footer__brand">
                    <a class="ef-brand" href="{{ route('home') }}">
                        <span class="ef-brand__mark" aria-hidden="true"></span>
                        <span>EventFlow</span>
                    </a>
                    <p class="ef-lead">
                        Discover, book, and run events worth making time for. One place from
                        first click to final scan.
                    </p>
                </div>

                <div class="ef-site-footer__columns">
                    <div class="ef-site-footer__column">
                        <p class="ef-eyebrow">Explore</p>
                        <ul>
                            <li><a href="{{ route('events.index') }}">All events</a></li>
                            <li><a href="{{ route('events.index', ['when' => 'today']) }}">Happening today</a></li>
                            <li><a href="{{ route('events.index', ['when' => 'weekend']) }}">This weekend</a></li>
                            <li><a href="{{ route('events.index', ['type' => 'free']) }}">Free events</a></li>
                        </ul>
                    </div>

                    <div class="ef-site-footer__column">
                        <p class="ef-eyebrow">Categories</p>
                        <ul>
                            @foreach (\App\Models\Event::CATEGORIES as $category)
                                <li><a href="{{ route('events.index', ['category' => $category]) }}">{{ ucwords(str_replace('-', ' ', $category)) }}</a></li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="ef-site-footer__column">
                        <p class="ef-eyebrow">Your account</p>
                        <ul>
                            @guest
                                <li><a href="{{ route('login') }}">Log in</a></li>
                                <li><a href="{{ route('register') }}">Create account</a></li>
                            @else
                                <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li><a href="{{ route('attendee.tickets') }}">My tickets</a></li>
                                @if (auth()->user()->isStaff())
                                    <li><a href="{{ route('admin.dashboard') }}">Admin workspace</a></li>
                                @endif
                            @endguest
                            <li><a href="{{ route('events.index') }}">Find an event</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="ef-site-footer__cta">
                <div>
                    <p class="ef-eyebrow">Host something</p>
                    <p class="ef-lead">Create an event, take payments, and run the door from one workspace.</p>
                </div>
                <x-ui.button :href="auth()->check() ? route('host.events.create') : route('register')">
                    {{ auth()->check() ? 'Create an event' : 'Become a host' }}
                </x-ui.button>
            </div>

            <div class="ef-site-footer__base">
                <p class="ef-meta">&copy; {{ now()->year }} EventFlow. All rights reserved.</p>
                <p class="ef-meta">Payments are processed securely and verified server-side before a ticket is issued.</p>
            </div>
        </div>
    </footer>

</body>
</html>
