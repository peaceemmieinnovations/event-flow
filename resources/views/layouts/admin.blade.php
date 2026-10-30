<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') &middot; @endif EventFlow Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ef-body ef-page--admin">
    <a class="ef-skip-link" href="#main-content">Skip to content</a>

    @php
        $navigation = [
            ['label' => 'Overview', 'route' => 'admin.dashboard', 'icon' => 'grid', 'active' => ['admin.dashboard', 'admin.analytics.index']],
            ['label' => 'Events', 'route' => 'admin.events.index', 'icon' => 'calendar', 'active' => ['admin.events.index', 'admin.events.create', 'admin.events.edit', 'admin.events.analytics']],
            ['label' => 'Registrations', 'route' => 'admin.registrations.index', 'icon' => 'users', 'active' => ['admin.registrations.index', 'admin.registrations.show']],
            ['label' => 'Tickets', 'route' => 'admin.tickets.index', 'icon' => 'ticket', 'active' => ['admin.tickets.index']],
            ['label' => 'Check-In', 'route' => 'admin.tickets.checkin.index', 'icon' => 'scan', 'active' => ['admin.tickets.checkin.index', 'admin.tickets.checkin', 'admin.tickets.checkin.process']],
            ['label' => 'Payments', 'route' => 'admin.payments.index', 'icon' => 'card', 'active' => ['admin.payments.index', 'admin.payments.show']],
            ['label' => 'Communications', 'route' => 'admin.communications.index', 'icon' => 'mail', 'active' => ['admin.communications.index', 'admin.communications.compose']],
            ['label' => 'Analytics', 'route' => 'admin.analytics.index', 'icon' => 'chart', 'active' => ['admin.analytics.index']],
        ];

        $current = $navigation;
    @endphp

    <div class="ef-admin-shell">
        <aside class="ef-admin-sidebar" id="admin-navigation" data-admin-sidebar>
            <div class="ef-admin-sidebar__brand">
                <a class="ef-brand" href="{{ route('admin.dashboard') }}">
                    <span class="ef-brand__mark" aria-hidden="true"></span>
                    <span>EventFlow</span>
                </a>
                <span class="ef-admin-sidebar__tag">Admin</span>
            </div>

            <nav class="ef-admin-sidebar__nav" aria-label="Admin navigation">
                @foreach ($current as $item)
                    @php $isActive = collect($item['active'])->contains(fn ($name) => request()->routeIs($name)); @endphp
                    <a class="ef-admin-sidebar__link @if ($isActive) is-active @endif" href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif>
                        <span class="ef-admin-sidebar__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                @switch($item['icon'])
                                    @case('grid')<rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                                    @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2.5"></rect><path d="M3 10h18M8 3v4M16 3v4"></path>
                                    @case('users')<circle cx="9" cy="8" r="3.25"></circle><path d="M3 20a6 6 0 0 1 12 0M16 5.5a3.25 3.25 0 0 1 0 5M18 20a5.5 5.5 0 0 0-3-4.9"></path>
                                    @case('ticket')<path d="M4 8.5V7a1.5 1.5 0 0 1 1.5-1.5h13A1.5 1.5 0 0 1 20 7v1.5a2.5 2.5 0 0 0 0 7V17a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 17v-1.5a2.5 2.5 0 0 0 0-7Z"></path><path d="M13 7.5v1M13 11v1.5M13 15.5v1"></path>
                                    @case('scan')<path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2M4 12h16"></path>
                                    @case('card')<rect x="2.5" y="5.5" width="19" height="13" rx="2.5"></rect><path d="M2.5 10h19M6 14.5h4"></path>
                                    @case('mail')<rect x="2.5" y="5" width="19" height="14" rx="2.5"></rect><path d="m3 7 9 6 9-6"></path>
                                    @case('chart')<path d="M4 20V4M4 20h16M8 17v-5M12.5 17V8M17 17v-8"></path>
                                @endswitch
                            </svg>
                        </span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="ef-admin-sidebar__footer">
                <a class="ef-admin-sidebar__link" href="{{ route('home') }}">
                    <span class="ef-admin-sidebar__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11 12 4l8 7v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1Z"></path></svg>
                    </span>
                    <span>View site</span>
                </a>
                <div class="ef-admin-sidebar__account">
                    <span class="ef-admin-avatar" aria-hidden="true">{{ auth()->user()->initials() }}</span>
                    <span class="ef-admin-sidebar__account-copy">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>{{ auth()->user()->roleLabel() }}</small>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="ef-admin-sidebar__logout" type="submit" title="Log out" aria-label="Log out">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17l5-5-5-5M20 12H9M12 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="ef-admin-main">
            <header class="ef-admin-topbar">
                <button class="ef-admin-topbar__toggle" type="button" data-admin-menu-toggle aria-expanded="false" aria-controls="admin-navigation" aria-label="Toggle admin navigation">
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                    <span aria-hidden="true"></span>
                </button>

                <div class="ef-admin-topbar__title">
                    <p class="ef-eyebrow">@yield('eyebrow', 'Workspace')</p>
                    <h1 class="ef-heading-md">@yield('title', 'Overview')</h1>
                </div>

                <div class="ef-admin-topbar__actions">
                    @yield('actions')
                </div>
            </header>

            <main id="main-content" class="ef-admin-content" tabindex="-1">
                <x-flash />
                @yield('content')
            </main>
        </div>
    </div>

    <div class="ef-admin-scrim" data-admin-scrim hidden></div>
</body>
</html>
