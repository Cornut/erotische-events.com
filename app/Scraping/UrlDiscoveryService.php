<?php

namespace App\Scraping;

use App\Models\Organizer;
use App\Scraping\Extractors\EventExtractor;
use App\Scraping\Llm\LlmClient;

/**
 * Uses the LLM to discover an organizer's event-listing / iCal URLs, then VALIDATES
 * each candidate with the AI-free extractors (JSON-LD + iCal) and returns only the
 * URLs that actually yield events. These are stored in organizers.scrape_urls so
 * recurring scrapes run without AI.
 */
class UrlDiscoveryService
{
    /**
     * @param  array<int, EventExtractor>  $structuredExtractors  AI-free extractors used for validation
     */
    public function __construct(
        private readonly PageFetcher $fetcher,
        private readonly LlmClient $llm,
        private readonly array $structuredExtractors,
    ) {}

    /**
     * @return array<int, string> Validated listing/feed URLs (each yields events AI-free).
     */
    public function discover(Organizer $organizer): array
    {
        $seed = $organizer->events_url ?: $organizer->website;
        if (empty($seed)) {
            return [];
        }

        $html = $this->fetcher->get($seed);
        if ($html === null && $organizer->website && $organizer->website !== $seed) {
            $seed = $organizer->website;
            $html = $this->fetcher->get($seed);
        }
        if ($html === null) {
            return [];
        }

        $valid = [];
        foreach (array_unique($this->llm->findEventUrls($html, $seed)) as $candidate) {
            $body = $this->fetcher->get($candidate);
            if ($body === null) {
                continue;
            }
            foreach ($this->structuredExtractors as $extractor) {
                if ($extractor->extract($body, $candidate) !== []) {
                    $valid[] = $candidate;
                    break;
                }
            }
        }

        return array_values(array_unique($valid));
    }
}
