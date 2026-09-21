<!DOCTYPE html>
<html>
<head>
    <title>@yield('title') - EventFlow</title>
</head>
<body>

    <header>
        <h1>EventFlow</h1>

        <nav>
           <a href="{{ route('home') }}">Home</a>
<a href="{{ route('events.index') }}">Events</a>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

</body>
</html>