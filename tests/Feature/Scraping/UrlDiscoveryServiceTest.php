<?php

use App\Models\Organizer;
use App\Scraping\Extractors\EventExtractor;
use App\Scraping\Extractors\IcalExtractor;
use App\Scraping\Llm\LlmClient;
use App\Scraping\PageFetcher;
use App\Scraping\ScrapedEvent;
use App\Scraping\UrlDiscoveryService;

it('keeps only LLM-suggested urls that actually yield events AI-free', function () {
    $organizer = Organizer::factory()->make(['website' => 'https://x.de', 'events_url' => 'https://x.de/termine']);

    // Fetcher returns content for every url (so validation depends on the extractor).
    $fetcher = new class implements PageFetcher
    {
        public function get(string $url): ?string
        {
            return 'content-of:'.$url;
        }
    };

    // LLM proposes two candidates.
    $llm = new class implements LlmClient
    {
        public function extractEvents(string $content, string $pageUrl): array
        {
            return [];
        }

        public function findEventUrls(string $content, string $baseUrl): array
        {
            return ['https://x.de/good-feed.ics', 'https://x.de/no-events'];
        }
    };

    // Structured extractor yields an event only for the "good" url.
    $structured = new class implements EventExtractor
    {
        public function extract(string $html, string $pageUrl): array
        {
            if ($pageUrl !== 'https://x.de/good-feed.ics') {
                return [];
            }
            $e = ScrapedEvent::fromArray([
                'title' => 'X', 'start_date' => '2026-01-01 10:00', 'source_url' => $pageUrl.'/1',
            ]);

            return $e ? [$e] : [];
        }
    };

    $urls = (new UrlDiscoveryService($fetcher, $llm, [$structured]))->discover($organizer);

    expect($urls)->toBe(['https://x.de/good-feed.ics']);
});

it('discovers an .ics feed one level deep (linked from a detail page) and validates it', function () {
    $organizer = Organizer::factory()->make(['website' => 'https://x.de', 'events_url' => 'https://x.de/termine']);

    $pages = [
        'https://x.de/termine' => '<a href="/detail/1.html">Event 1</a>',
        'https://x.de/detail/1.html' => '<a href="/ics/1.ics">In Kalender</a>',
        'https://x.de/ics/1.ics' => "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:Tantra Festival\r\nDTSTART:20260730T100000\r\nURL:https://x.de/e/1\r\nEND:VEVENT\r\nEND:VCALENDAR",
    ];

    $fetcher = new class($pages) implements PageFetcher
    {
        public function __construct(private array $pages) {}

        public function get(string $url): ?string
        {
            return $this->pages[$url] ?? null;
        }
    };

    $llm = new class implements LlmClient
    {
        public function extractEvents(string $content, string $pageUrl): array
        {
            return [];
        }

        public function findEventUrls(string $content, string $baseUrl): array
        {
            return []; // no direct candidates — rely on the deep crawl
        }
    };

    $service = new UrlDiscoveryService($fetcher, $llm, [new IcalExtractor]);

    expect($service->discover($organizer))->toBe(['https://x.de/ics/1.ics']);
});
