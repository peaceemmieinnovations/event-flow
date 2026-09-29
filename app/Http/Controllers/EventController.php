<?php

namespace App\Http\Controllers;

use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::query()
            ->where('status', 'published')
            ->orderBy('event_date')
            ->get();

        return view('events', [
            'events' => $events,
        ]);
    }

    public function show(Event $event)
    {
        abort_unless($event->status === 'published', 404);

        return view('events.show', [
            'event' => $event,
        ]);
    }

    public function createTestEvent()
    {
        $event = new Event();

        $event->title = 'Laravel Beginners Conference';
        $event->description = 'A practical introduction to Laravel development.';
        $event->location = 'Akure, Nigeria';
        $event->event_date = '2026-10-15 10:00:00';

        $event->save();

        return 'Event created successfully!';
    }
}
