<?php

namespace App\Http\Controllers;

use App\Models\Event;

class HomeController extends Controller
{
    public function index()
    {
        $homeEvents = Event::query()
            ->where('status', 'published')
            ->orderBy('event_date')
            ->limit(6)
            ->get();

        $featuredEvents = $homeEvents->take(3);
        $discoveryEvents = $homeEvents->slice(3)->values();

        if ($discoveryEvents->isEmpty() && $featuredEvents->isNotEmpty()) {
            $discoveryEvents = $featuredEvents->take(1)->values();
        }

        return view('home', [
            'featuredEvents' => $featuredEvents,
            'discoveryEvents' => $discoveryEvents,
            'categories' => $this->categoryDiscovery(),
        ]);
    }

    private function categoryDiscovery(): array
    {
        return [
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
    }
}
