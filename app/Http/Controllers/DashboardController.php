<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $registrations = Registration::query()
            ->where('user_id', $user->id)
            ->with(['event', 'ticket'])
            ->latest('registered_at')
            ->get();

        $upcoming = $registrations
            ->filter(fn (Registration $registration): bool => (bool) $registration->event?->event_date?->isFuture())
            ->sortBy(fn (Registration $registration) => $registration->event->event_date)
            ->values();

        $past = $registrations
            ->reject(fn (Registration $registration): bool => (bool) $registration->event?->event_date?->isFuture())
            ->sortByDesc(fn (Registration $registration) => $registration->event?->event_date)
            ->values();

        $recommended = Event::query()
            ->published()
            ->where('event_date', '>=', now())
            ->whereNotIn('id', $upcoming->pluck('event_id')->filter())
            ->withCount(['registrations as registration_count' => fn ($query) => $query->whereIn('status', Event::ACTIVE_REGISTRATION_STATUSES)])
            ->orderByDesc('registration_count')
            ->orderBy('event_date')
            ->take(3)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'upcoming' => $upcoming,
            'past' => $past,
            'tickets' => $upcoming->pluck('ticket')->filter()->values(),
            'recommended' => $recommended,
            'statistics' => [
                'total' => $registrations->count(),
                'confirmed' => $registrations->where('status', Registration::STATUS_CONFIRMED)->count(),
                'upcoming' => $upcoming->count(),
                'attended' => $registrations->where('status', Registration::STATUS_ATTENDED)->count(),
            ],
        ]);
    }
}
