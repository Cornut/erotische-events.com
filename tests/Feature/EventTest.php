<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventPrice;
use App\Models\Organizer;
use App\Models\Venue;

it('creates a draft event with json audience/languages and relations', function () {
    $organizer = Organizer::factory()->create();
    $venue = Venue::factory()->create(['organizer_id' => $organizer->id]);
    $event = Event::factory()->create([
        'organizer_id' => $organizer->id,
        'venue_id' => $venue->id,
        'audience' => ['couples', 'everyone'],
        'languages' => ['de', 'en'],
    ]);

    expect($event->status)->toBe(EventStatus::Draft)
        ->and($event->audience)->toBe(['couples', 'everyone'])
        ->and($event->organizer->is($organizer))->toBeTrue()
        ->and($event->venue->is($venue))->toBeTrue();
});

it('has many prices and soft-deletes', function () {
    $event = Event::factory()->create();
    EventPrice::factory()->create(['event_id' => $event->id]);

    expect($event->prices)->toHaveCount(1);

    $event->delete();
    expect(Event::count())->toBe(0)->and(Event::withTrashed()->count())->toBe(1);
});

it('scopes published events', function () {
    Event::factory()->create(['status' => EventStatus::Draft]);
    Event::factory()->create(['status' => EventStatus::Published]);

    expect(Event::published()->count())->toBe(1);
});
