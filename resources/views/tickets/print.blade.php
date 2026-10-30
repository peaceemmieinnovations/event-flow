<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Ticket {{ $ticket->displayNumber() }} &middot; EventFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ef-body ef-page--ticket-print">
    <a class="ef-skip-link" href="#main-content">Skip to content</a>

    <main id="main-content" class="ef-ticket-print__stage" tabindex="-1">
        <div class="ef-ticket-print__toolbar" data-print-toolbar>
            <p class="ef-meta">Print this page, or open it as a PDF and present it at the door.</p>
            <div class="ef-actions">
                <button class="ef-button ef-button--primary" type="button" data-print-button>Print ticket</button>
                <a class="ef-button ef-button--secondary" href="{{ route('tickets.show', $ticket->token) }}">Back to ticket</a>
            </div>
        </div>

        @include('tickets.partials.card', ['ticket' => $ticket, 'mode' => 'print'])
    </main>
</body>
</html>
