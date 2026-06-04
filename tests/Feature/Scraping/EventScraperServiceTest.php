<?php

use App\Models\Event;
use App\Models\Organizer;
use App\Scraping\EventImportService;
use App\Scraping\EventScraperService;
use App\Scraping\EventsUrlResolver;
use App\Scraping\Extractors\EventExtractor;
use App\Scraping\PageFetcher;
use App\Scraping\ScrapedEvent;

it('fetches, extracts and imports events for an organizer', function () {
    $org = Organizer::factory()->approved()->create(['website' => 'https://x.de', 'events_url' => 'https://x.de/termine']);

    $fetcher = new class implements PageFetcher
    {
        public function get(string $url): ?string
        {
            return $url === 'https://x.de/termine' ? '<html>list</html>' : null;
        }
    };

    $extractor = new class implements EventExtractor
    {
        public function extract(string $html, string $pageUrl): array
        {
            return [ScrapedEvent::fromArray([
                'title' => 'Scraped Event',
                'start_date' => '2026-09-01 10:00',
                'source_url' => 'https://x.de/e/1',
            ])];
        }
    };

    $service = new EventScraperService($fetcher, [$extractor], app(EventImportService::class), new EventsUrlResolver);
    $result = $service->scrape($org);

    expect($result['created'])->toBe(1)
        ->and(Event::where('source_url', 'https://x.de/e/1')->exists())->toBeTrue()
        ->and($org->fresh()->last_scraped_at)->not->toBeNull();
});
