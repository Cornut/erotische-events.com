<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;

it('lists only published events publicly', function () {
    Event::factory()->published()->create(['title' => 'Visible']);
    Event::factory()->create(['status' => EventStatus::Draft, 'title' => 'Hidden']);

    $this->get('/events')->assertSuccessful();
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
