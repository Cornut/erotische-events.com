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

        foreach ($this->resolver->candidates($organizer) as $url) {
            $body = $this->fetcher->get($url);
            if ($body === null) {
                continue;
            }
            foreach ($chain as $extractor) {
                $events = $extractor->extract($body, $url);
                if ($events !== []) {
                    return [...$this->importer->import($organizer, $events), 'url' => $url];
                }
            }
        }

        return ['created' => 0, 'updated' => 0, 'url' => null];
    }
}
