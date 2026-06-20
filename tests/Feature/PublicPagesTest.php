<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;
use Inertia\Testing\AssertableInertia;

it('lists only published events publicly', function () {
    Event::factory()->published()->create(['title' => 'Visible']);
    Event::factory()->create(['status' => EventStatus::Draft, 'title' => 'Hidden']);

    $this->get('/events')->assertSuccessful();
});

it('filters the public event listing by search query', function () {
    Event::factory()->published()->create(['title' => 'Tantra Weekend']);
    Event::factory()->published()->create(['title' => 'Cooking Class']);

    $this->get('/events?q=tantra')->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('Public/Events/Index')
            ->has('events.data', 1)
            ->where('events.data.0.title', 'Tantra Weekend')
    );
});

it('shows a published event detail page', function () {
    $event = Event::factory()->published()->create();
    $this->get("/events/{$event->slug}")->assertSuccessful();
});

it('returns 404 for a non-published event detail', function () {
    $event = Event::factory()->create(['status' => EventStatus::Draft]);
    $this->get("/events/{$event->slug}")->assertNotFound();
});

it('shows an organizer public profile', function () {
    $organizer = Organizer::factory()->approved()->create();
    $this->get("/organizers/{$organizer->slug}")->assertSuccessful();
});

it('paginates the public listing so infinite scroll can load further pages', function () {
    Event::factory()->count(30)->published()->create();

    $this->get('/events')->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('events.current_page', 1)
            ->where('events.last_page', 3)
            ->has('events.data', 12)
    );

    $this->get('/events?page=2')->assertInertia(
        fn (AssertableInertia $page) => $page
            ->where('events.current_page', 2)
            ->has('events.data', 12)
    );
});

it('obfuscates the outbound url on the event detail page (no raw booking_url in props)', function () {
    $event = Event::factory()->published()->create(['booking_url' => 'https://organizer.test/secret-page']);

    $this->get("/events/{$event->slug}")->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('Public/Events/Show')
            ->missing('event.booking_url')
            ->missing('event.source_url')
    );
});

it('rebuilds a filtered back-link when arriving from the events list', function () {
    $event = Event::factory()->published()->create();

    $this->get("/events/{$event->slug}?from=events&q=tantra&category=tantra&countries[0]=CH")
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Public/Events/Show')
                ->where('eventsBackUrl', fn (?string $url) => $url !== null
                    && str_contains($url, 'q=tantra')
                    && str_contains($url, 'category=tantra')
                    && str_contains($url, 'CH'))
        );
});

it('has no events back-link without the from=events marker', function () {
    $event = Event::factory()->published()->create();

    $this->get("/events/{$event->slug}?q=tantra")->assertInertia(
        fn (AssertableInertia $page) => $page
            ->component('Public/Events/Show')
            ->where('eventsBackUrl', null)
    );
});

it('does not echo unknown query params into the back-link (no open redirect)', function () {
    $event = Event::factory()->published()->create();

    $this->get("/events/{$event->slug}?from=events&evil=https://attacker.test&q=safe")
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Public/Events/Show')
                ->where('eventsBackUrl', fn (?string $url) => $url !== null
                    && str_starts_with($url, '/events?')
                    && ! str_contains($url, 'attacker'))
        );
});
