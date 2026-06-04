<?php

use App\Scraping\Extractors\StructuredDataExtractor;

it('extracts schema.org Event JSON-LD from html', function () {
    $html = <<<'HTML'
    <html><head>
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"Event","name":"Tantra Weekend",
     "startDate":"2026-09-01T10:00","endDate":"2026-09-03T17:00",
     "url":"https://x.de/e/1","location":{"@type":"Place","address":{"addressLocality":"Hamburg"}},
     "offers":{"@type":"Offer","price":"199","priceCurrency":"EUR"}}
    </script></head><body></body></html>
    HTML;

    $events = (new StructuredDataExtractor())->extract($html, 'https://x.de/termine');

    expect($events)->toHaveCount(1);
    $e = $events[0];
    expect($e->title)->toBe('Tantra Weekend')
        ->and($e->city)->toBe('Hamburg')
        ->and($e->sourceUrl)->toBe('https://x.de/e/1')
        ->and($e->prices[0]['amount'])->toBe(199.0)
        ->and($e->prices[0]['currency'])->toBe('EUR');
});

it('returns empty array when no JSON-LD present', function () {
    expect((new StructuredDataExtractor())->extract('<html></html>', 'https://x.de'))->toBe([]);
});
