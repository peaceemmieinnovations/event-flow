<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Ticket;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Curated category discovery. Labels and copy live here so the homepage can
     * stay editorial, while real filtering happens against the category slug.
     *
     * @return list<array{slug: string, name: string, description: string, image: string, image_alt: string}>
     */
    private const CATEGORY_CONTENT = [
        [
            'slug' => 'music',
            'name' => 'Music',
            'description' => 'Live sets, late nights, and front-row energy.',
            'image' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1600&q=82',
            'image_alt' => 'A musician performing on stage beneath concert lights',
        ],
        [
            'slug' => 'arts-culture',
            'name' => 'Arts & Culture',
            'description' => 'Exhibitions, ideas, and work made to stay with you.',
            'image' => 'https://images.unsplash.com/photo-1561214115-f2f134cc4912?auto=format&fit=crop&w=1200&q=82',
            'image_alt' => 'Visitors viewing colorful artwork in a gallery',
        ],
        [
            'slug' => 'food-drink',
            'name' => 'Food & Drink',
            'description' => 'Tables worth gathering around.',
            'image' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1200&q=82',
            'image_alt' => 'A beautifully arranged dinner table in a restaurant',
        ],
        [
            'slug' => 'business',
            'name' => 'Business',
            'description' => 'Conversations that move ideas forward.',
            'image' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=82',
            'image_alt' => 'Professionals collaborating around a table',
        ],
        [
            'slug' => 'wellness',
            'name' => 'Wellness',
            'description' => 'Make time to reset, move, and reconnect.',
            'image' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=1200&q=82',
            'image_alt' => 'People practicing yoga together in a bright studio',
        ],
        [
            'slug' => 'community',
            'name' => 'Community',
            'description' => 'The people and places that bring a city together.',
            'image' => 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=1200&q=82',
            'image_alt' => 'Friends gathering together at an outdoor community event',
        ],
    ];

    public function index(): View
    {
        $upcoming = Event::query()
            ->published()
            ->where('event_date', '>=', now()->subDay())
            ->orderBy('event_date')
            ->take(6)
            ->get();

        // Past-but-recent events still deserve a homepage slot when the
        // calendar is thin, so the page never renders an empty shell.
        $recent = $upcoming->isEmpty()
            ? Event::query()->published()->orderByDesc('event_date')->take(3)->get()
            : collect();

        $featuredEvents = ($upcoming->isEmpty() ? $recent : $upcoming)->take(3)->values();
        $discoveryEvents = ($upcoming->isEmpty() ? $recent : $upcoming)->slice(3)->values();

        return view('home', [
            'featuredEvents' => $featuredEvents,
            'discoveryEvents' => $discoveryEvents,
            'categories' => $this->categoryDiscovery(),
            'statistics' => $this->statistics(),
            'workflow' => $this->workflowSteps(),
        ]);
    }

    /**
     * @return list<array<string, string>>
     */
    private function categoryDiscovery(): array
    {
        return self::CATEGORY_CONTENT;
    }

    /**
     * Real platform numbers, aggregated in a handful of queries.
     *
     * @return array<string, mixed>
     */
    private function statistics(): array
    {
        return [
            'events' => Event::query()->count(),
            'published_events' => Event::published()->count(),
            'upcoming_events' => Event::published()->where('event_date', '>=', now())->count(),
            'registrations' => Registration::query()->whereIn('status', Event::ACTIVE_REGISTRATION_STATUSES)->count(),
            'tickets' => Ticket::query()->count(),
            'check_ins' => Ticket::query()->where('status', Event::TICKET_STATUS_CHECKED_IN)->count(),
            'cities' => Event::query()->whereNotNull('city')->distinct()->count('city'),
            'revenue' => (int) Payment::query()->whereIn('status', Payment::REVENUE_STATUSES)->sum('amount'),
        ];
    }

    /**
     * @return list<array{title: string, copy: string}>
     */
    private function workflowSteps(): array
    {
        return [
            [
                'title' => 'Create and publish',
                'copy' => 'Set the date, venue, capacity and price. Drafts stay private until you publish them.',
            ],
            [
                'title' => 'Share the event',
                'copy' => 'Attendees discover it through the calendar, search and category browsing.',
            ],
            [
                'title' => 'Register or check out',
                'copy' => 'Free events confirm instantly. Paid events verify the payment server-side.',
            ],
            [
                'title' => 'Ticket and check-in',
                'copy' => 'Every confirmed attendee gets a QR ticket your team can scan at the door.',
            ],
        ];
    }
}
