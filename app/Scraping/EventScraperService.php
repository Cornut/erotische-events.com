<?php

namespace App\Scraping;

use App\Models\Organizer;
use App\Scraping\Extractors\EventExtractor;

class EventScraperService
{
    /**
     * @param  array<int, EventExtractor>  $structuredExtractors  AI-free extractors (JSON-LD, iCal)
     * @param  EventExtractor|null  $llmExtractor  optional AI fallback (only used in auto-discovery)
     */
    public function __construct(
        private readonly PageFetcher $fetcher,
        private readonly array $structuredExtractors,
        private readonly ?EventExtractor $llmExtractor,
        private readonly EventImportService $importer,
        private readonly EventsUrlResolver $resolver,
    ) {}

    /**
     * @return array{created: int, updated: int, url: ?string}
     */
    public function scrape(Organizer $organizer): array
    {
        $manualUrls = $organizer->scrapeUrls();

        $result = $manualUrls !== []
            ? $this->scrapeCuratedUrls($organizer, $manualUrls)
            : $this->autoDiscover($organizer);

        $organizer->forceFill(['last_scraped_at' => now()])->save();

        return $result;
    }

    /**
     * Curated per-organizer URLs: fetch EACH, extract with AI-free extractors only,
     * aggregate all events, import once.
     *
     * @param  array<int, string>  $urls
     * @return array{created: int, updated: int, url: ?string}
     */
    private function scrapeCuratedUrls(Organizer $organizer, array $urls): array
    {
        $events = [];
        $used = null;

        foreach ($urls as $url) {
            $body = $this->fetcher->get($url);
            if ($body === null) {
                continue;
            }
            foreach ($this->structuredExtractors as $extractor) {
                foreach ($extractor->extract($body, $url) as $event) {
                    $events[] = $event;
                    $used = $used ?? $url;
                }
            }
        }

        if ($events === []) {
            return ['created' => 0, 'updated' => 0, 'url' => null];
        }

        return [...$this->importer->import($organizer, $events), 'url' => $used];
    }

    /**
     * Auto-discovery: try resolver candidates, structured extractors then the LLM
     * fallback, stop at the first URL that yields events.
     *
     * @return array{created: int, updated: int, url: ?string}
     */
    private function autoDiscover(Organizer $organizer): array
    {
        $chain = $this->llmExtractor !== null
            ? [...$this->structuredExtractors, $this->llmExtractor]
            : $this->structuredExtractors;

        $seedHtml = null;
        $seedUrl = null;

        foreach ($this->resolver->candidates($organizer) as $url) {
            $body = $this->fetcher->get($url);
            if ($body === null) {
                continue;
            }
            $seedHtml ??= $body;
            $seedUrl ??= $url;
            foreach ($chain as $extractor) {
                $events = $extractor->extract($body, $url);
                if ($events !== []) {
                    return [...$this->importer->import($organizer, $events), 'url' => $url];
                }
            }
        }

        // Overview pages held no events directly — crawl one level deeper onto
        // same-domain detail pages and let the chain (LLM included) read each.
        if ($seedHtml !== null) {
            return $this->deepCrawl($organizer, $seedHtml, $seedUrl, $chain);
        }

        return ['created' => 0, 'updated' => 0, 'url' => null];
    }

    /**
     * Follow same-domain links from a seed page (capped), run the extractor chain
     * on each sub-page (structured first, LLM only if nothing structured), and
     * aggregate all events into a single import.
     *
     * @param  array<int, EventExtractor>  $chain
     * @return array{created: int, updated: int, url: ?string}
     */
    private function deepCrawl(Organizer $organizer, string $html, string $base, array $chain): array
    {
        $max = (int) config('scraping.max_detail_pages', 25);
        $events = [];
        $used = null;

        foreach (array_slice(LinkExtractor::sameDomainLinks($html, $base), 0, $max) as $page) {
            $body = $this->fetcher->get($page);
            if ($body === null) {
                continue;
            }
            foreach ($chain as $extractor) {
                $found = $extractor->extract($body, $page);
                if ($found !== []) {
                    foreach ($found as $event) {
                        $events[] = $event;
                    }
                    $used ??= $page;
                    break; // first extractor that yields wins for this page
                }
            }
        }

        if ($events === []) {
            return ['created' => 0, 'updated' => 0, 'url' => null];
        }

        return [...$this->importer->import($organizer, $events), 'url' => $used];
    }
}
