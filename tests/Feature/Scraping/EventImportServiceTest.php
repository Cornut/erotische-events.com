<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organizer;
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
