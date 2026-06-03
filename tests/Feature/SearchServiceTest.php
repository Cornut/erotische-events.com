<?php

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventPrice;
use App\Models\Organizer;
use App\Models\Venue;
use App\Search\SearchService;

beforeEach(fn () => $this->service = app(SearchService::class));

it('finds published events by text query', function () {
    Event::factory()->published()->create(['title' => 'Tantra Weekend Retreat']);
    Event::factory()->published()->create(['title' => 'Cooking Class']);

    $results = $this->service->search(['q' => 'tantra']);

    expect($results->total())->toBe(1)
        ->and($results->first()->title)->toBe('Tantra Weekend Retreat');
});

it('excludes unpublished events from search', function () {
    Event::factory()->create(['title' => 'Hidden Tantra', 'status' => EventStatus::Draft]);

    expect($this->service->search(['q' => 'tantra'])->total())->toBe(0);
});

it('filters by category slug', function () {
    $cat = Category::create(['slug' => 'tantra', 'name_de' => 'Tantra', 'name_en' => 'Tantra']);
    $match = Event::factory()->published()->create();
    $match->categories()->attach($cat);
    Event::factory()->published()->create();

    $results = $this->service->search(['category' => 'tantra']);
    expect($results->total())->toBe(1)->and($results->first()->is($match))->toBeTrue();
});

it('filters by city via the venue', function () {
    $organizer = Organizer::factory()->create();
    $berlinVenue = Venue::factory()->create(['organizer_id' => $organizer->id, 'city' => 'Berlin']);
    $match = Event::factory()->published()->create(['venue_id' => $berlinVenue->id]);
    Event::factory()->published()->create();

    $results = $this->service->search(['city' => 'Berlin']);
    expect($results->total())->toBe(1)->and($results->first()->is($match))->toBeTrue();
});

it('filters by maximum price', function () {
    $cheap = Event::factory()->published()->create();
    EventPrice::factory()->create(['event_id' => $cheap->id, 'amount' => 80]);
    $pricey = Event::factory()->published()->create();
    EventPrice::factory()->create(['event_id' => $pricey->id, 'amount' => 400]);

    $results = $this->service->search(['price_max' => 100]);
    expect($results->total())->toBe(1)->and($results->first()->is($cheap))->toBeTrue();
});

it('filters by geo radius using a bounding box', function () {
    $organizer = Organizer::factory()->create();
    $near = Venue::factory()->create(['organizer_id' => $organizer->id, 'latitude' => 52.52, 'longitude' => 13.40]);
    $far = Venue::factory()->create(['organizer_id' => $organizer->id, 'latitude' => 48.13, 'longitude' => 11.58]);
    $nearEvent = Event::factory()->published()->create(['venue_id' => $near->id]);
    Event::factory()->published()->create(['venue_id' => $far->id]);

    $results = $this->service->search(['lat' => 52.52, 'lng' => 13.40, 'radius_km' => 25]);
    expect($results->total())->toBe(1)->and($results->first()->is($nearEvent))->toBeTrue();
});
