<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HostEventController extends Controller
{
    public function create(Request $request): View
    {
        return view('host.events.create', [
            'event' => new Event([
                'event_type' => Event::TYPE_FREE,
                'currency' => 'NGN',
                'organizer_name' => $request->user()->name,
                'organizer_email' => $request->user()->email,
            ]),
            'categories' => Event::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['user_id'] = $request->user()->id;
        $data['status'] = Event::STATUS_PENDING_REVIEW;

        Event::create($data);

        return to_route('dashboard')->with('status', 'Your event has been submitted for review. We will publish it once it is approved.');
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'event_date' => ['required', 'date', 'after:today'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::in(Event::CATEGORIES)],
            'organizer_name' => ['required', 'string', 'max:255'],
            'organizer_email' => ['required', 'email', 'max:255'],
            'organizer_phone' => ['nullable', 'string', 'max:40'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'event_type' => ['required', Rule::in(Event::TYPES)],
            'price_amount' => ['nullable', 'numeric', 'min:0.01', 'max:100000000'],
            'currency' => ['required', 'string', 'size:3'],
            'registration_deadline' => ['nullable', 'date', 'before_or_equal:event_date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=400,min_height=250'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['currency'] = strtoupper($data['currency']);

        if ($data['event_type'] === Event::TYPE_FREE) {
            $data['price_amount'] = 0;
        } else {
            $data['price_amount'] = (int) round(((float) $data['price_amount']) * 100);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('events', 'public');
        }

        return $data;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $suffix = 2;

        while (Event::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
