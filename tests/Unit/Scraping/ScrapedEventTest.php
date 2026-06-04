<?php

use App\Scraping\ScrapedEvent;

it('builds from an array with defaults', function () {
    $e = ScrapedEvent::fromArray([
        'title' => 'Tantra Weekend',
        'start_date' => '2026-09-01 10:00',
        'source_url' => 'https://x.de/e/1',
        'prices' => [['amount' => 199.0, 'currency' => 'EUR']],
    ]);

    expect($e->title)->toBe('Tantra Weekend')
        ->and($e->sourceUrl)->toBe('https://x.de/e/1')
        ->and($e->prices)->toHaveCount(1)
        ->and($e->endDate)->toBeNull()
        ->and($e->bookingUrl)->toBe('https://x.de/e/1');
});

it('ignores entries without a title or start date', function () {
    expect(ScrapedEvent::fromArray(['source_url' => 'x']))->toBeNull();
});
