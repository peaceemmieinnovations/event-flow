<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    /**
     * The admin landing screen doubles as the platform analytics screen, so the
     * team always opens the app on real numbers.
     */
    public function index(Request $request): View
    {
        $analytics = $this->analytics->overview($request->query());

        return view('admin.dashboard', [
            ...$analytics,
            'ranges' => AnalyticsService::RANGES,
            'events' => Event::query()
                ->orderByDesc('event_date')
                ->limit(6)
                ->get(['id', 'title', 'event_date', 'status', 'capacity']),
        ]);
    }

    public function event(Request $request, Event $event): View
    {
        $analytics = $this->analytics->forEvent($event, $request->query());

        return view('admin.events.analytics', [
            'event' => $event,
            ...$analytics,
            'ranges' => AnalyticsService::RANGES,
            'breakdown' => $this->analytics->registrationBreakdown($analytics['range'], $event),
        ]);
    }
}
