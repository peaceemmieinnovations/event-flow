<?php

namespace Tests\Feature;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_calendar_only_lists_published_upcoming_events(): void
    {
        Event::factory()->published()->create(['title' => 'Visible Tomorrow', 'event_date' => now()->addDay()]);
        Event::factory()->draft()->create(['title' => 'Hidden Draft', 'event_date' => now()->addDay()]);
        Event::factory()->cancelled()->create(['title' => 'Hidden Cancelled', 'event_date' => now()->addDay()]);
        Event::factory()->published()->create(['title' => 'Hidden Past', 'event_date' => now()->subDay()]);

        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('Visible Tomorrow')
            ->assertDontSee('Hidden Draft')
            ->assertDontSee('Hidden Cancelled')
            ->assertDontSee('Hidden Past');
    }

    public function test_search_matches_title_location_city_and_organizer(): void
    {
        Event::factory()->published()->create([
            'title' => 'Lagos Design Summit',
            'city' => 'Lagos',
            'event_date' => now()->addDay(),
        ]);
        Event::factory()->published()->create([
            'title' => 'Unrelated Session',
            'location' => 'Island Hall',
            'city' => 'Ibadan',
            'organizer_name' => 'Ibadan Collective',
            'event_date' => now()->addDay(),
        ]);

        $this->get(route('events.index', ['q' => 'Lagos']))
            ->assertOk()
            ->assertSee('Lagos Design Summit')
            ->assertDontSee('Unrelated Session');
    }

    public function test_search_looks_beyond_the_title(): void
    {
        Event::factory()->published()->create([
            'title' => 'Quiet Workshop',
            'city' => 'Enugu',
            'event_date' => now()->addDay(),
        ]);
        Event::factory()->published()->create([
            'title' => 'Plain Meetup',
            'city' => 'Lagos',
            'event_date' => now()->addDay(),
        ]);

        $this->get(route('events.index', ['q' => 'Lagos']))
            ->assertOk()
            ->assertSee('Plain Meetup')
            ->assertDontSee('Quiet Workshop');
    }

    public function test_the_weekend_filter_excludes_the_weekdays_that_follow_it(): void
    {
        // Anchor to the Saturday itself so the events are ahead of us, matching
        // how the calendar always hides past dates.
        $this->travelTo(Carbon::parse('2026-10-03 09:00:00'));

        Event::factory()->published()->create(['title' => 'Saturday Session', 'event_date' => Carbon::parse('2026-10-03 14:00:00')]);
        Event::factory()->published()->create(['title' => 'Sunday Session', 'event_date' => Carbon::parse('2026-10-04 14:00:00')]);
        // An end-of-week window would wrongly include both of these.
        Event::factory()->published()->create(['title' => 'Monday Session', 'event_date' => Carbon::parse('2026-10-05 14:00:00')]);
        Event::factory()->published()->create(['title' => 'Friday Session', 'event_date' => Carbon::parse('2026-10-09 14:00:00')]);

        $this->get(route('events.index', ['when' => 'weekend']))
            ->assertOk()
            ->assertSee('Saturday Session')
            ->assertSee('Sunday Session')
            ->assertDontSee('Monday Session')
            ->assertDontSee('Friday Session');
    }

    public function test_the_weekend_filter_offers_the_upcoming_weekend_mid_week(): void
    {
        // On a Tuesday the visitor means "the coming weekend", not an empty
        // calendar or the days that have already passed.
        $this->travelTo(Carbon::parse('2026-10-06 09:00:00'));

        Event::factory()->published()->create(['title' => 'Upcoming Saturday', 'event_date' => Carbon::parse('2026-10-10 14:00:00')]);
        Event::factory()->published()->create(['title' => 'Upcoming Sunday', 'event_date' => Carbon::parse('2026-10-11 14:00:00')]);
        Event::factory()->published()->create(['title' => 'Weekday Thing', 'event_date' => Carbon::parse('2026-10-07 14:00:00')]);

        $this->get(route('events.index', ['when' => 'weekend']))
            ->assertOk()
            ->assertSee('Upcoming Saturday')
            ->assertSee('Upcoming Sunday')
            ->assertDontSee('Weekday Thing');
    }

    public function test_the_free_and_paid_filters_are_exclusive(): void
    {
        Event::factory()->free()->published()->create(['title' => 'Free Clinic', 'event_date' => now()->addDay()]);
        Event::factory()->paid()->published()->create(['title' => 'Paid Workshop', 'event_date' => now()->addDay()]);

        $this->get(route('events.index', ['type' => 'free']))
            ->assertOk()
            ->assertSee('Free Clinic')
            ->assertDontSee('Paid Workshop');

        $this->get(route('events.index', ['type' => 'paid']))
            ->assertOk()
            ->assertSee('Paid Workshop')
            ->assertDontSee('Free Clinic');
    }

    public function test_city_and_category_filters_narrow_the_calendar(): void
    {
        Event::factory()->published()->create([
            'title' => 'Abuja Tech Meetup',
            'city' => 'Abuja',
            'category' => Event::CATEGORIES[0],
            'event_date' => now()->addDay(),
        ]);
        Event::factory()->published()->create([
            'title' => 'Port Harcourt Clinic',
            'city' => 'Port Harcourt',
            'category' => Event::CATEGORIES[1],
            'event_date' => now()->addDay(),
        ]);

        $this->get(route('events.index', ['city' => 'Abuja']))
            ->assertOk()
            ->assertSee('Abuja Tech Meetup')
            ->assertDontSee('Port Harcourt Clinic');

        $this->get(route('events.index', ['category' => Event::CATEGORIES[1]]))
            ->assertOk()
            ->assertSee('Port Harcourt Clinic')
            ->assertDontSee('Abuja Tech Meetup');
    }

    public function test_an_unknown_category_is_ignored_rather_than_erroring(): void
    {
        Event::factory()->published()->create(['title' => 'Still Listed', 'event_date' => now()->addDay()]);

        $this->get(route('events.index', ['category' => 'not-a-category']))
            ->assertOk()
            ->assertSee('Still Listed');
    }

    public function test_sorting_by_title_is_alphabetical(): void
    {
        Event::factory()->published()->create(['title' => 'Zebra Workshop', 'event_date' => now()->addDay()]);
        Event::factory()->published()->create(['title' => 'Alpha Workshop', 'event_date' => now()->addDays(5)]);

        $response = $this->get(route('events.index', ['sort' => 'title']))->assertOk();

        $alpha = $response->getContent();
        $this->assertLessThan(
            strpos((string) $alpha, 'Zebra Workshop'),
            strpos((string) $alpha, 'Alpha Workshop'),
            'Alphabetical sorting must place Alpha before Zebra.',
        );
    }

    public function test_a_draft_event_detail_page_is_not_public(): void
    {
        $event = Event::factory()->draft()->create();

        $this->get(route('events.show', $event))->assertNotFound();
    }

    public function test_a_published_event_detail_page_shows_related_events_from_the_same_category(): void
    {
        $event = Event::factory()->published()->create([
            'category' => Event::CATEGORIES[0],
            'event_date' => now()->addDay(),
        ]);

        Event::factory()->published()->create([
            'title' => 'Same Category Neighbour',
            'category' => Event::CATEGORIES[0],
            'event_date' => now()->addDays(3),
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('More events for you')
            ->assertSee('Same Category Neighbour');
    }

    public function test_the_related_section_is_suppressed_when_there_is_nothing_to_show(): void
    {
        $event = Event::factory()->published()->create(['event_date' => now()->addDay()]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertDontSee('More events for you');
    }

    public function test_the_related_section_never_includes_past_events(): void
    {
        $event = Event::factory()->published()->create(['event_date' => now()->addDay()]);

        Event::factory()->published()->create([
            'title' => 'Event That Already Happened',
            'event_date' => now()->subDay(),
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertDontSee('Event That Already Happened');
    }

    public function test_an_event_detail_page_never_lists_itself_as_related(): void
    {
        $event = Event::factory()->published()->create(['title' => 'Anchor Event', 'event_date' => now()->addDay()]);

        $related = $this->get(route('events.show', $event))->assertOk();

        // The title may legitimately appear once as the page heading; it must not
        // appear as a second, related card linking back to itself.
        $this->assertSame(1, substr_count((string) $related->getContent(), route('events.show', $event)));
    }

    public function test_public_screens_are_open_to_guests(): void
    {
        $event = Event::factory()->published()->create();

        $this->get(route('events.index'))->assertOk();
        $this->get(route('events.show', $event))->assertOk();
        $this->get(route('home'))->assertOk();
    }
}
