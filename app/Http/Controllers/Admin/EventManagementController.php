<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventManagementController extends Controller
{
    public function index(Request $request): View
    {
        $events = Event::query()->withCount(['registrations as registration_count' => fn ($query) => $query->whereIn('status', ['pending', 'confirmed'])])
            ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->input('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('event_type', $request->input('type')))
            ->orderBy('event_date')
            ->get();

        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('admin.events.form', ['event' => new Event(), 'mode' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = Event::create($this->eventData($request));

        return to_route('admin.events.index')->with('status', "{$event->title} has been created.");
    }

    public function edit(Event $event): View
    {
        return view('admin.events.form', compact('event') + ['mode' => 'edit']);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $event->update($this->eventData($request, $event));

        return to_route('admin.events.index')->with('status', "{$event->title} has been updated.");
    }

    public function destroy(Event $event): RedirectResponse
    {
        if ($event->image && ! Str::startsWith($event->image, ['http://', 'https://', '/'])) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return to_route('admin.events.index')->with('status', 'Event deleted.');
    }

    public function setStatus(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'cancelled', 'completed'])]]);
        $event->update($data);

        return back()->with('status', "{$event->title} is now {$event->status}.");
    }

    private function eventData(Request $request, ?Event $event = null): array
    {
        if (blank($request->input('slug'))) {
            $request->merge(['slug' => Str::slug((string) $request->input('title'))]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('events', 'slug')->ignore($event)],
            'description' => ['required', 'string'], 'event_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'], 'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'], 'state' => ['nullable', 'string', 'max:255'], 'country' => ['nullable', 'string', 'max:255'],
            'organizer_name' => ['nullable', 'string', 'max:255'], 'organizer_email' => ['nullable', 'email', 'max:255'], 'organizer_phone' => ['nullable', 'string', 'max:40'],
            'capacity' => ['nullable', 'integer', 'min:1'], 'event_type' => ['required', Rule::in(['free', 'paid'])],
            'status' => ['required', Rule::in(['draft', 'published', 'cancelled', 'completed'])],
            'registration_deadline' => ['nullable', 'date', 'before_or_equal:event_date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=400,min_height=250'],
        ]);

        if ($request->hasFile('image')) {
            if ($event?->image && ! Str::startsWith($event->image, ['http://', 'https://', '/'])) Storage::disk('public')->delete($event->image);
            $data['image'] = $request->file('image')->store('events', 'public');
        } else unset($data['image']);

        return $data;
    }
}
