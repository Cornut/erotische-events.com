<?php

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Venue;

it('builds a searchable array with related names and geo', function () {
    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->id, 'latitude' => 52.52, 'longitude' => 13.405]);
    $event = Event::factory()->published()->create(['organizer_id' => $organizer->id, 'venue_id' => $venue->id]);

    $array = $event->toSearchableArray();

    expect($array)->toHaveKeys(['id', 'title', 'organizer', 'categories', '_geo'])
        ->and($array['_geo'])->toMatchArray(['lat' => 52.52, 'lng' => 13.405]);
});

it('is searchable only when published', function () {
    $published = Event::factory()->published()->create();
    $draft = Event::factory()->create();

    expect($published->shouldBeSearchable())->toBeTrue()
        ->and($draft->shouldBeSearchable())->toBeFalse();
});
