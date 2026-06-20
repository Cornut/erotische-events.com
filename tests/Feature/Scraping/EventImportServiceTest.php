<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Teacher;
use App\Scraping\EventImportService;
use App\Scraping\ScrapedEvent;

function importer(): EventImportService
{
    return app(EventImportService::class);
}

function scraped(array $overrides = []): ScrapedEvent
{
    return ScrapedEvent::fromArray(array_merge([
        'title' => 'Tantra Weekend',
        'start_date' => '2026-09-01 10:00',
        'source_url' => 'https://x.de/e/1',
        'prices' => [['amount' => 100.0, 'currency' => 'CHF']],
    ], $overrides));
}

it('imports a scraped event as published with EUR price and organizer context', function () {
    $org = Organizer::factory()->approved()->create(['category' => 'tantra']);

    importer()->import($org, [scraped()]);

    $event = Event::where('source_url', 'https://x.de/e/1')->firstOrFail();
    expect($event->status)->toBe(EventStatus::Published)
        ->and($event->organizer_id)->toBe($org->id)
        ->and($event->currency)->toBe('EUR')
        ->and((float) $event->prices->first()->amount)->toBe(105.0) // 100 CHF * 1.05
        ->and($event->prices->first()->currency)->toBe('EUR');
});

it('keeps the original-currency price as text in the description (EUR stays structured)', function () {
    $org = Organizer::factory()->approved()->create();

    importer()->import($org, [scraped([
        'description' => 'Ein schönes Wochenende.',
        'prices' => [['amount' => 420.0, 'currency' => 'CHF']],
    ])]);

    $event = Event::where('source_url', 'https://x.de/e/1')->firstOrFail();

    // EUR stays the structured, comparable value...
    expect((float) $event->prices->first()->amount)->toBe(441.0)
        ->and($event->prices->first()->currency)->toBe('EUR')
        // ...and the original currency is preserved in the text.
        ->and($event->long_description)->toContain('420')
        ->and($event->long_description)->toContain('CHF')
        ->and($event->long_description)->toContain('Ein schönes Wochenende.');
});

it('adds no price note when the event has no prices', function () {
    $org = Organizer::factory()->approved()->create();

    importer()->import($org, [scraped(['description' => 'Nur Text.', 'prices' => []])]);

    $event = Event::where('source_url', 'https://x.de/e/1')->firstOrFail();
    expect($event->long_description)->toBe('Nur Text.');
});

it('reuses the organizer venue when the event address is identical', function () {
    $org = Organizer::factory()->approved()->create();
    $home = \App\Models\Venue::factory()->create([
        'organizer_id' => $org->id, 'street' => 'Hauptstraße 1', 'city' => 'Berlin',
    ]);

    importer()->import($org, [scraped([
        'street' => 'Hauptstraße 1', 'city' => 'Berlin',
    ])]);

    $event = Event::where('source_url', 'https://x.de/e/1')->firstOrFail();
    expect($event->venue_id)->toBe($home->id)
        ->and($org->venues()->count())->toBe(1); // no duplicate venue
});

it('creates a new venue when the event is at a different address', function () {
    $org = Organizer::factory()->approved()->create();
    \App\Models\Venue::factory()->create([
        'organizer_id' => $org->id, 'street' => 'Hauptstraße 1', 'city' => 'Berlin',
    ]);

    importer()->import($org, [scraped([
        'venue_name' => 'Seminarhaus Wendland', 'street' => 'Dorfweg 7', 'city' => 'Hamburg',
    ])]);

    $event = Event::where('source_url', 'https://x.de/e/1')->firstOrFail();
    expect($org->venues()->count())->toBe(2)
        ->and($event->venue->city)->toBe('Hamburg')
        ->and($event->venue->street)->toBe('Dorfweg 7');
});

it('creates a venue from the event address when the organizer has none', function () {
    $org = Organizer::factory()->approved()->create();

    importer()->import($org, [scraped(['street' => 'Seeweg 3', 'city' => 'Zürich'])]);

    $event = Event::where('source_url', 'https://x.de/e/1')->firstOrFail();
    expect($event->venue)->not->toBeNull()
        ->and($event->venue->city)->toBe('Zürich');
});

it('does not duplicate a created venue across events at the same address', function () {
    $org = Organizer::factory()->approved()->create();

    importer()->import($org, [
        scraped(['source_url' => 'https://x.de/e/1', 'street' => 'Seeweg 3', 'city' => 'Zürich']),
        scraped(['source_url' => 'https://x.de/e/2', 'street' => 'Seeweg 3', 'city' => 'Zürich']),
    ]);

    expect($org->venues()->count())->toBe(1);
});

it('is idempotent on re-import (updates, not duplicates)', function () {
    $org = Organizer::factory()->approved()->create();
    importer()->import($org, [scraped(['title' => 'V1'])]);
    importer()->import($org, [scraped(['title' => 'V2'])]);

    expect(Event::where('source_url', 'https://x.de/e/1')->count())->toBe(1)
        ->and(Event::where('source_url', 'https://x.de/e/1')->first()->title)->toBe('V2');
});

it('never touches manually created events (source_url null)', function () {
    $org = Organizer::factory()->approved()->create();
    $manual = Event::factory()->create(['organizer_id' => $org->id, 'source_url' => null, 'title' => 'Manual']);

    importer()->import($org, [scraped()]);

    expect($manual->fresh()->title)->toBe('Manual');
});

it('creates teachers and shares one record across events and organizers', function () {
    $orgA = Organizer::factory()->approved()->create();
    $orgB = Organizer::factory()->approved()->create();

    importer()->import($orgA, [scraped(['source_url' => 'https://a.de/e1', 'teachers' => ['Jane Doe']])]);
    importer()->import($orgB, [scraped(['source_url' => 'https://b.de/e9', 'teachers' => ['Jane Doe']])]);

    expect(Teacher::where('slug', 'jane-doe')->count())->toBe(1);

    $teacher = Teacher::where('slug', 'jane-doe')->firstOrFail();
    expect($teacher->events()->count())->toBe(2);
});
